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

### Extension Configuration
```(yml)
debug:
    enabled: true
    address: noreply@example.com # email used to send debug notifications

subscribers:
    contenttype: subscribers # content type with the subscribers data
    emailfield: email        # email field name

# templates:
#     emailbody: extensions/bolt-sendemail_fornewcontent/email.twig
#     emailsubject: extensions/bolt-sendemail_fornewcontent/_subject.twig

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
#             subject: New entry published
#             from_name:      # Default : Site name
#             from_email:     # 
#             replyto_name:   #
#             replyto_email:  #
#         templates:          # Over ride the global Twig templates for this form
#             emailbody: extensions/bolt-sendemail_fornewcontent/email.twig
#             emailsubject: extensions/bolt-sendemail_fornewcontent/_subject.twig
```
