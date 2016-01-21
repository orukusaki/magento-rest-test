# Magento Rest Tester

A simple CLI tool for testing the Magento REST API.

Manually testing REST is a pain in Magento, as all requests must include an oauth signature.
This tool helps you complete the "oauth dance" and issue arbitrary rest commands to your magento installation.

## Usage

Start by installing [composer](https://getcomposer.org/) dependencies:

    $ php composer.phar install

### Authorize

    php bin/rest-test.php authorize [-p|--platform_id <platform_id>]

Run this command first.  The platform id flag allows you to store multiple named configurations (staging, production etc). Pick whatever you like, but remember it for later.

You'll be prompted for your Magento Base Url, Consumer Key and Consumer Secret (set these up in Magento Admin).
The script will then initialise an oAuth session and try to open a browser window to allow you to authorise the application.
It listens on port 8000 for the callback.  Once the authorisation has completed, the token is stored in config.json.

### Request

    php bin/rest-test.php request [-p|--platform_id <platform_id>] [-m|--http_verb GET|DELETE|POST|PUT] [-t|--request_type <request_type>] [-c|--request_content <request_content>] <http_resource>

Use this command to issue a REST request.  The results are displayed as json.  Use the platform id flag to load the config and tokens you set up previously in the authorise step.

GET Example:

    $ php bin/rest-test.php request -plocal /customers
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

PUT Example:

    $ php bin/rest-test.php request -plocal -mPUT /customers/1 -c'{"firstname": "Bob"}' -tjson
    Sending: https://missguided.development.local/api/rest/customers/2
    Request body: {"firstname": "Bob"}
    null

    $ php bin/rest-test.php request -plocal /customers/1
    Sending: https://magento.local/api/rest/customers/1
    {
        "entity_id": "1",
        "website_id": "1",
        "email": "1432138706@example.com",
        "group_id": "1",
        "created_at": "2015-05-20 16:19:15",
        "disable_auto_group_change": "0",
        "firstname": "Bob",
        "lastname": "lastname",
        "created_in": "UK",
        "dob": "1980-01-01 00:00:00"
    }


### Troubleshooting

If you get an error response from Magento, your token might have expired.  Try running authorize again.

If you get the following error response body back from your Magento installation (if you are running a VM) when trying to authenticate:

     [Symfony\Component\Process\Exception\RuntimeException]
      oauth_problem=timestamp_refused

Your client is more than 5 minutes out of sync with your server and authentication will fail. Try updating the time of your VM.

### Contributing

PRs, Issues welcomed on [github](https://github.com/orukusaki/magento-rest-test)
