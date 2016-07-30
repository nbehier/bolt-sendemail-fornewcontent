Send email for new content - Bolt Extension
===========================================

[Bolt](https://bolt.cm/) extension to send email when new content is published

### Requirements
- Bolt 3.x installation
- [optional] [Newsletter Subscription](https://github.com/miguelavaqrod/bolt-newsletter-subscription) to save registered emails in database.

### Installation
1. Login to your Bolt installation
2. Go to "View/Install Extensions" (Hover over "Extras" menu item)
3. Type `sendemail-fornewcontent` into the input field
4. Click on the extension name
5. Click on "Browse Versions"
6. Click on "Install This Version" on the latest stable version

### Set up
Email notifications will be sent, you should configure the `mailoptions` setting in your Bolt `app/config/config.yml`.

**Note:** This extension uses the Swiftmailer library to send email notifications, based on the `mailoptions:` setting in your Bolt `app/config/config.yml` file.

**Tip:** If you want to modify the HTML templates, you should copy the `.yml` file to your `theme/` folder, and modify it there. Any changes in the file in the distribution might be overwritten after an update to the extension. For instance, if you copy `email.twig` to `theme/base-2016/my_email.twig`, the corresponding line in `config.yml` should be: `emailbody: my_email.twig`

### Extension Configuration
```(yml)
debug:
    enabled: true
    address: noreply@example.com # email used to send debug notifications

subscribers:
    contenttype: subscribers # content type with the subscribers data
    emailfield: email        # email field name

# templates:
#     emailbody: extensions/bolt-sendemail_fornewcontent/email_body.twig
#     emailsubject: extensions/bolt-sendemail_fornewcontent/email_subject.twig

email:
    from_name:  Your website
    from_email: your-email@your-website.com
#     replyto_name:   #
#     replyto_email:  #

notifications:
    entries:               # contenttype
#         enabled: true
#         event: new-pusblished
#         subscribers:
#             contenttype: subscribers
#             emailfield: email
#             filter:
#                 field: newcontentsubscription
#                 value: true
#         debug:   true
#         email:
#             from_name:      # Default : Site name
#             from_email:     #
#             replyto_name:   #
#             replyto_email:  #
#         templates:          # Over ride the global Twig templates for this form
#             emailbody: extensions/bolt-sendemail_fornewcontent/email_body.twig
#             emailsubject: extensions/bolt-sendemail_fornewcontent/email_subject.twig
```

### Credits
Globally inspired by [BoltForms](https://github.com/bolt/boltforms) and [BoltBB](https://github.com/GawainLynch/bolt-extension-boltbb)

### License
This Bolt extension is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
