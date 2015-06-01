<?php
namespace Orukusaki\MagentoRestTest;

use Cilex\Application;
use Cilex\ServiceProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use RuntimeException;

/**
 * Class OauthClientProvider
 * @package Orukusaki\MagentoRestTest
 */
class OauthClientProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        $app['client_factory'] = $app->protect(
            function ($platformId) use ($app) {

                $config = $app['config'];

                if (!property_exists($config, $platformId)) {
                    throw new RuntimeException(
                        "Couldn't find platform details for $platformId. Run the 'authorize' command first."
                    );
                }

                $platformDetails = (array) $config->$platformId;

                $client = new Client(
                    [
                        'base_url' => $platformDetails['base_url'],
                        'defaults' => [
                            'auth' => 'oauth',
                            'verify' => false,
                            'headers' => [
                                'Accept'=> 'application/json'
                            ]
                        ],
                    ]
                );

                $platformDetails['callback'] = 'http://127.0.0.1:8000';

                $oauth = new Oauth1($platformDetails);

                $client->getEmitter()->attach($oauth);

                return $client;
            }
        );
    }
}
