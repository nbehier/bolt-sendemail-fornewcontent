<?php

namespace Bolt\Extension\Leskis\BoltSendEmailForNewContent;

use Bolt\Events\StorageEvent;
use Bolt\Events\StorageEvents;
use Bolt\Extension\SimpleExtension;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * BoltSendEmailForNewContent extension class.
 *
 * @author Nicolas Béhier-Dévigne
 */
class BoltSendEmailForNewContentExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        // Pre-save hook
        $dispatcher->addListener(StorageEvents::PRE_SAVE, [$this, 'hookPreSave']);
    }

    /**
     * Pre-save hook
     * @param \Bolt\Events\StorageEvent $event
     */
    public function hookPreSave(StorageEvent $event)
    {
        $app = $this->getContainer();

        // Get contenttype
        $contenttype                = $event->getContentType();
        $aNotificationsContentTypes = $this->getNotifContentTypes();

        if (   empty($contenttype)
            || empty($aNotificationsContentTypes)
            || !in_array($contenttype, $aNotificationsContentTypes) ) {
            return;
        }

        // Get the record : Bolt\Storage\Entity\Content
        $record = $event->getContent();

        // Test if newly published
        $contentNewlyPublished = false;
        if (   $event->isCreate()
            && $record->getStatus() == 'published') {
            $contentNewlyPublished = true;
        }
        else if ($record->getStatus() == 'published') {
            // @todo : check if notification already sent
            // use a temporary file or a db table ?
            $repo = $app['storage']->getRepository($contenttype);
            $oldRecord = $repo->find($record->getId() );
            if (   !empty($oldRecord)
                && $oldRecord->getStatus() != 'published') {
                $contentNewlyPublished = true;
            }
        }

        if ($contentNewlyPublished) {
            // Launch the notification
            $notify = new Notifications($app, $this->getConfig(), $record, $contenttype);

            // Search subscribers
            try {
                $aSubscribers = $this->getSubscribers($contenttype);

                // Send email foreach subscriber
                $notify->doNotification($aSubscribers);
            } catch (\Exception $e) {
                $app['logger.system']->error(sprintf("BoltSendEmailForNewContentExtension notifications can't be sent - %s", $e->getMessage() ), ['event' => 'extensions']);
                return;
            }
        }

        return;
    }

    /**
     * Get Subscribers for notifications
     * @throws Exception
     * @return array records
     */
    private function getSubscribers($contenttype)
    {
        $app          = $this->getContainer();
        $config       = $this->getConfig();
        $subConfig    = $config['subscribers'];
        $aSubscribers = false;

        // Check if specific config inside ContentType config
        if ( ! empty($contenttype) && array_key_exists('subscribers', $config['notifications'][$contenttype]) ) {
            $subConfig = $config['notifications'][$contenttype]['subscribers'];
        }

        $entityName   = $subConfig['contenttype'];
        $fieldName    = $subConfig['emailfield'];
        $filter       = array_key_exists('filter', $subConfig) ? $subConfig['filter'] : false;

        try {
            $meta = $app['storage.metadata']->getClassMetadata($entityName);

            // Check if config email field exists
            if (   array_key_exists($fieldName, $meta['fields'])
                && $meta['fields'][$fieldName]['type'] == 'string' ) {

                $repo = $app['storage']->getRepository($entityName);

                // Apply query filter if necessary
                if ($filter) {
                    $aSubscribers = $repo->findBy([$filter['field'] => $filter['value']]);
                }
                else {
                    $aSubscribers = $repo->findAll();
                }
            }

        } catch (\Exception $e) {
            if ($filter) {
                throw new \Exception(sprintf("No Subscribers table like %s with email field %s, filtered by %s = %s, as defined on config", $entityName, $fieldName, $filter['field'], $filter['value']), 1);
            }
            else {
                throw new \Exception(sprintf("No Subscribers table like %s with email field %s, as defined on config", $entityName, $fieldName), 1);
            }
        }

        return $aSubscribers;
    }

    /**
     * Get ContentTypes which have subscriptions
     * @return array
     */
    private function getNotifContentTypes()
    {
        $config        = $this->getConfig();
        $aContentTypes = array_keys($config['notifications']);

        foreach ($aContentTypes as $k => $sContentType) {
            if (   array_key_exists('enabled', $config['notifications'][$sContentType])
                && $config['notifications'][$sContentType]['enabled'] == false ) {
                unset($aContentTypes[$k]);
            }
        }

        return $aContentTypes;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'debug' => [
                'enabled' => true
            ],

            'templates' => [
                'emailbody'    => 'email_body.twig',
                'emailsubject' => 'email_subject.twig'
            ],

            'notifications' => [
                'entries' => [
                    'enabled'     => true
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return 'Send email for new content';
    }
}
