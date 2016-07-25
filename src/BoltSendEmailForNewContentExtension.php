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
        // Post-save hook for topic and reply creations
        $dispatcher->addListener(StorageEvents::POST_SAVE, [$this, 'hookPostSave']);
    }
    
    /**
     * Post-save hook for topic and reply creations
     *
     * @param \Bolt\Events\StorageEvent $event
     */
    public function hookPostSave(StorageEvent $event)
    {
        // Get contenttype
        $contenttype = $event->getContentType();
        $aNotificationsContentTypes = $this->getNotifContentTypes();
        if (empty($contenttype) || empty($aNotificationsContentTypes) || !in_array($contenttype, $aNotificationsContentTypes) ) {
            return;
        }
    
        // Get the newly saved record
        $record = $event->getContent();
    
        // Launch the notification
        $notify = new Notifications($this->app, $record);
        $notify->doNotification();
    }
    
    /**
     * Get ContentTypes which have subscriptions
     * @todo
     */
    private function getNotifContentTypes()
    {
        $config = $this->getConfig();
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
