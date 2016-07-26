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
                $aSubscribers = $this->getSubscribers();

                // Send email foreach subscriber
                // @todo
                //$notify->doNotification();
            } catch (\Exception $e) {
                //$this->app['logger.system']->info("Notifications to {$recipient['displayName']} <{$recipient['email']}>", ['event' => 'extensions']);
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
    private function getSubscribers()
    {
        $config       = $this->getConfig();
        $entityName   = $config['subscribers']['contenttype'];
        $fieldName    = $config['subscribers']['emailfield'];
        $aSubscribers = false;

        try {
            $meta = $app['storage.metadata']->getClassMetadata($entityName);//'Bolt\Storage\Entity\Users');
            if (array_key_exists($fieldName, $meta['fields'])
                && $meta['fields'][$fieldName]['type'] == 'string' ) {
                $repo = $app['storage']->getRepository($entityName);
                $aSubscribers = $app['storage']->fetchAll();
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf("No Subscribers table like %s with email field %s, as defined on config", $entityName, $fieldName), 1);
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
        return $aNotificationsContentTypes;
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
