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
        $dispatcher->addListener(StorageEvents::PRE_SAVE, [$this, 'hookPreSave']);
    }
    
    /**
     * Post-save hook for topic and reply creations
     *
     * @param \Bolt\Events\StorageEvent $event
     */
    public function hookPreSave(StorageEvent $event)
    {
        // Get contenttype
        $contenttype = $event->getContentType();
        $aNotificationsContentTypes = $this->getNotifContentTypes();
        if (empty($contenttype) || empty($aNotificationsContentTypes) || !in_array($contenttype, $aNotificationsContentTypes) ) {
            return;
        }
    
        // Get the record
        $record = $event->getContent();
        
        // Test if newly published
        // @todo create and published
        if (false) {
            // Launch the notification
            $notify = new Notifications($this->app, $record);
            
            // Search subscribers
            // @todo
            
            // Send email foreach subscriber
            // @todo
            $notify->doNotification();
        }
        else {
            // @todo : check if notification already sent
            // use a temporary file or a db table ?
        }
    }
    
    /**
     * Get ContentTypes which have subscriptions
     */
    private function getNotifContentTypes()
    {
        $config = $this->getConfig();
        // @todo
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
