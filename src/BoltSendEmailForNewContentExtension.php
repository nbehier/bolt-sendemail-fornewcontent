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
        // Get contenttype
        $contenttype                = $event->getContentType();
        $aNotificationsContentTypes = $this->getNotifContentTypes();
        if (   empty($contenttype)
            || empty($aNotificationsContentTypes)
            || !in_array($contenttype, $aNotificationsContentTypes) ) {
            return;
        }

        // Get the record
        $record = $event->getContent();

        // Test if newly published
        $contentNewlyPublished = false;
        if (   $event->isCreate()
            && array_key_exists('status', $record->values)
            && $record->values['status'] == 'published') {
            $contentNewlyPublished = true;
        }
        else if ($record->values['status'] == 'published') {
            // @todo : check if notification already sent
            // use a temporary file or a db table ?
            $repo = $app['storage']->getRepository($contenttype);
            $oldRecord = $repo->find($record->id);
            if (   !empty($oldRecord)
                && $oldRecord->values['status'] != 'published') {
                $contentNewlyPublished = true;
            }
        }

        if ($contentNewlyPublished) {
            // Launch the notification
            $notify = new Notifications($this->app, $record);

            // Search subscribers
            try {
                $aSubscribers = $this->getSubscribers($contenttype);

                // Send email foreach subscriber
                $notify->doNotification($aSubscribers);
            } catch (\Exception $e) {
                $this->app['logger.system']->error(sprintf("Notifications can't be sent - %s", $e->getMessage() ), ['event' => 'extensions']);
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
            $meta = $app['storage.metadata']->getClassMetadata($entityName);//'Bolt\Storage\Entity\Users');

            // Check if config email field exists
            if (   array_key_exists($fieldName, $meta['fields'])
                && $meta['fields'][$fieldName]['type'] == 'string' ) {

                $repo = $app['storage']->getRepository($entityName);

                // Apply query filter if necessary
                if ($filter) {
                    $aSubscribers = $app['storage']->findBy([$filter['field'] => $filter['value']]);
                }
                else {
                    $aSubscribers = $app['storage']->findAll();
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
                'enabled' => true,
                'address' => 'noreply@example.com'
            ],

            'subscribers' => [
                'contenttype' => 'subscribers',
                'emailfield'  => 'email'
            ],

            'templates' => [
                'emailbody'    => '@BoltSendEmailForNewContent/email.twig',
                'emailsubject' => '@BoltSendEmailForNewContent/_subject.twig'
            ],

            'email' => [
                'subject'       => 'New entry published',
                'from_name'     => '',
                'from_email'    => '',
                'replyto_name'  => '',
                'replyto_email' => ''
            ],

            'notifications' => [
                'entries' => [
                    'enabled'     => true,
                    'event'       => 'new-pusblished',
                    'subscribers' => [
                        'contenttype' => 'subscribers',
                        'emailfield'  => 'email',
                        'filter'      => [
                            'field' => 'newcontentsubscription',
                            'value' => true
                        ],
                    ],
                    'debug' =>   true,
                    'email' => [
                        'subject'       => 'New entry published',
                        'from_name'     => '',
                        'from_email'    => '',
                        'replyto_name'  => '',
                        'replyto_email' => ''
                    ],
                    'templates' => [
                        'emailbody'    => '@BoltSendEmailForNewContent/email.twig',
                        'emailsubject' => '@BoltSendEmailForNewContent/_subject.twig'
                    ],
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
