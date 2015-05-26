# Magento Rest Tester

A simple CLI tool for testing the Magento REST API.

Manually testing REST is a pain in Magento, as all requests must include an oauth signature.
This tool helps you complete the "oauth dance" and issue arbitrary rest commands to your magento installation.

## Commands
### Authorize

    php bin/rest-test.php authorize [-p|--platform="..."]

Run this command first.  You'll be prompted for your consumer key and secret (set these up in Magento Admin).
The script will then initialise an oAuth session and try to open a browser window to allow you to authorise the application.
It listens on port 8000 for the callback.  Once the authorisation has completed, the token is stored in config.json.

The platform flag allows you to store multiple named configurations (staging, production etc).

### Request

    php bin/rest-test.php request [-p|--platform="..."] [-m|--method[="..."]] request

Use this command to issue a REST request.  The results are displayed as json.
example:
    $ php bin/rest-test.php request -p local api/rest/customers
    Sending: https://magento.local/api/rest/customers
    {
        "1": {
            "entity_id": "1",
            "website_id": "1",
            "email": "1432138706@example.com",
            "group_id": "1",
            "created_at": "2015-05-20 16:19:15",
            "disable_auto_group_change": "0",
            "firstname": "firstname",
            "lastname": "lastname",
            "created_in": "UK",
            "dob": "1980-01-01 00:00:00"
        },
        "2": {
            "entity_id": "2",
            "website_id": "1",
            "email": "1432195999@example.com",
            "group_id": "1",
            "created_at": "2015-05-21 08:14:04",
            "disable_auto_group_change": "0",
            "firstname": "firstname",
            "lastname": "lastname",
            "created_in": "UK",
            "dob": "1980-01-01 00:00:00"
        }
    }

### Troubleshooting

If you get an error response from Magento, your token might have expired.  Try running authorize again.

### Contributing

PRs, Issues welcomed on [github][https://github.com/orukusaki/magento-rest-test]
