<?php

namespace Bolt\Extension\Leskis\BoltSendEmailForNewContent;

use Bolt\Extension\SimpleExtension;

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
