<?php

namespace Bolt\Extension\Leskis\BoltSendEmailForNewContent;

use Silex;
use \Bolt\Storage\Entity\Content;

/**
 * Notification class
 *
 * Copyright (C) 2014-2016 Gawain Lynch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @copyright Copyright (c) 2014, Gawain Lynch
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 */
class Notifications
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var string
     */
    private $debug_address;

    /**
     * @var \Bolt\Content
     */
    private $record;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string
     */
    private $emailField;

    /**
     * @var string
     */
    private $subjectTpl;

    /**
     * @var string
     */
    private $bodyTpl;

    /**
     * @var string
     */
    private $from_name;

    /**
     * @var string
     */
    private $from_email;

    /**
     * @var string
     */
    private $replyto_name;

    /**
     * @var string
     */
    private $replyto_email;

    /**
     * @param Silex\Application $app
     * @param \Bolt\Content     $record
     */
    public function __construct(Silex\Application $app, array $config, Content $record)
    {
        $this->app           = $app;
        $this->config        = $config;
        $this->debug         = $this->config['debug']['enabled'];
        $this->debug_address = $this->config['debug']['address'];
        $this->record        = $record;

        $this->setVars();
    }

    /**
     *
     */
    public function doNotification($recipients)
    {
        // Sort out the "to whom" list
        if ($this->debug) {
            $this->recipients = [
                new Content([
                    'title'  => 'Test Showcase',
                    'slug'   => 'test',
                    $this->emailField => $this->debug_address
                ])
            ];
        } else {
            // Get the subscribers
            $this->recipients = $recipients;
        }

        foreach ($this->recipients as $recipient) {
            // Get the email template
            $this->doCompose($recipient);

            $this->doSend($this->message, $recipient);
        }
    }

    /**
     * Compose the email data to be sent
     */
    private function doCompose($recipient)
    {
        /*
         * Subject
         */
        $html = $this->app['render']->render($this->subjectTpl, [
            'record' => $this->record
        ]);
        $subject = new \Twig_Markup($html, 'UTF-8');

        /*
         * Body
         */
        $html = $this->app['render']->render($this->bodyTpl, [
            'record'    => $this->record,
            'recipient' => $recipient
        ]);
        $body = new \Twig_Markup($html, 'UTF-8');


        /*
         * Build email
         */
        $this->message = $this->app['mailer']
                ->createMessage('message')
                ->setSubject($subject)
                ->setFrom([$this->from_email => $this->from_name])
                ->setBody(strip_tags($body))
                ->addPart($body, 'text/html');

        // Add reply to if necessary
        if (! empty($this->replyto_email) ) {
            $this->message->setReplyTo([$this->replyto_email => $this->replyto_name]);
        }
    }

    /**
     * Send a notification to a single user
     *
     * @param \Swift_Message $message
     * @param array          $recipient
     */
    private function doSend(\Swift_Message $message, Content $recipient)
    {
        // Set the recipient for *this* message
        $emailTo = $recipient->get($this->emailField);
        $message->setTo($emailTo);

        if ($this->app['mailer']->send($message)) {
            $this->app['logger.system']->info("Sent BoltSendEmailForNewContentExtension notification to {$emailTo}", ['event' => 'extensions']);
        } else {
            $this->app['logger.system']->error("Failed BoltSendEmailForNewContentExtension notification to {$emailTo}", ['event' => 'extensions']);
        }
    }

    /**
     * Set Config vars
     */
    private function setVars()
    {
        // Set ContentType from record
        $this->contentType = $this->record->getContenttype();

        // Set Email Field From Subscribers
        $this->emailField = $this->config['subscribers']['emailfield'];
        if (   ! empty($this->contentType)
            && array_key_exists('subscribers', $this->config['notifications'][$this->contentType]) ) {
            if ( isset($this->config['notifications'][$this->contentType]['subscribers']['emailfield']) ) {
                $this->emailField = $this->config['notifications'][$this->contentType]['subscribers']['emailfield'];
            }
        }

        // Get Templates
        $this->subjectTpl = $this->config['templates']['emailsubject'];
        $this->bodyTpl    = $this->config['templates']['emailbody'];
        if (   ! empty($this->contentType)
            && array_key_exists('templates', $this->config['notifications'][$this->contentType]) ) {
            if ( isset($this->config['notifications'][$this->contentType]['templates']['emailsubject']) ) {
                $this->subjectTpl = $this->config['notifications'][$this->contentType]['templates']['emailsubject'];
            }
            if ( isset($this->config['notifications'][$this->contentType]['templates']['bodysubject']) ) {
                $this->bodyTpl = $this->config['notifications'][$this->contentType]['templates']['bodysubject'];
            }
        }

        // Get Sender
        $this->from_name     = $this->config['email']['from_name'];
        $this->from_email    = $this->config['email']['from_email'];
        $this->replyto_name  = $this->config['email']['replyto_name'];
        $this->replyto_email = $this->config['email']['replyto_email'];
        if (   ! empty($this->contentType)
            && array_key_exists('email', $this->config['notifications'][$this->contentType]) ) {
            if ( isset($this->config['notifications'][$this->contentType]['email']['from_name'])
                 && ! empty($this->config['notifications'][$this->contentType]['email']['from_name']) ) {
                $this->from_name = $this->config['notifications'][$this->contentType]['email']['from_name'];
            }
            if ( isset($this->config['notifications'][$this->contentType]['email']['from_email'])
                 && ! empty($this->config['notifications'][$this->contentType]['email']['from_email']) ) {
                $this->from_email = $this->config['notifications'][$this->contentType]['email']['from_email'];
            }
            if ( isset($this->config['notifications'][$this->contentType]['email']['replyto_name'])
                 && ! empty($this->config['notifications'][$this->contentType]['email']['replyto_name']) ) {
                $this->replyto_name = $this->config['notifications'][$this->contentType]['email']['replyto_name'];
            }
            if ( isset($this->config['notifications'][$this->contentType]['email']['replyto_email'])
                 && ! empty($this->config['notifications'][$this->contentType]['email']['replyto_email']) ) {
                $this->replyto_email = $this->config['notifications'][$this->contentType]['email']['replyto_email'];
            }
        }
    }
}
