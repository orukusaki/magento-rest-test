<?php

namespace Orukusaki\MagentoRestTest;

use Cilex\Command\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use stdClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * Class AuthoriseCommand
 * @package Orukusaki\MagentoRestTest
 */
class AuthoriseCommand extends Command
{

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('authorize')
            ->setDescription('Do the "oauth dance" to get a request token')
            ->addOption('platform_id', 'p', InputOption::VALUE_REQUIRED, 'Platform Id (from config.json)');
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $platformId = $input->getOption('platform_id');
        $config = $this->getService('config');

        if (property_exists($config, $platformId)) {

            $platformConfig = $config->$platformId;
            unset($platformConfig->token);
            unset($platformConfig->token_secret);
            unset($platformConfig->verifier);
            $this->getContainer()['config'] = $config;
        }
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platformId = $input->getOption('platform_id');

        $output->writeln('Initiating Oauth');
        $vars = $this->getResponse($platformId, '/oauth/initiate');

        $output->writeln('Request Token: ' . $vars['oauth_token']);
        $output->writeln('Secret: ' . $vars['oauth_token_secret']);

        $config = $this->getService('config');

        $url = $config->$platformId->base_url . '/' . $config->$platformId->admin_url_front_name . '/oauth_authorize?oauth_token=' . $vars['oauth_token'];
        exec('open '. escapeshellarg($url));

        $output->writeln("Verify this token at $url");
        $output->writeln("Listening for callback..");

        $socket = stream_socket_server("tcp://127.0.0.1:8000", $errno, $errstr);
        $conn = stream_socket_accept($socket);
        $line = fgets($conn);

        $output->writeln('recv: ' . $line);

        $parts = explode(" ", $line);
        parse_str(preg_replace('~/\?~', '', $parts[1]), $query);

        if (isset($query['rejected'])) {
            fclose($conn);
            fclose($socket);
            throw new RuntimeException("Authorisation rejected :-(\n");
        }

        fwrite($conn, "HTTP/1.1 200 Found\n\nAuthorised! (look back at the script)\n\n");
        fclose($conn);
        fclose($socket);

        // The request token must be sent when requesting the access token
        $config->$platformId->token = $vars['oauth_token'];
        $config->$platformId->token_secret = $vars['oauth_token_secret'];
        $config->$platformId->verifier = $query['oauth_verifier'];

        $vars = $this->getResponse($platformId, '/oauth/token');

        // Now we have an access token, which we'll save in the config
        $config->$platformId->token = $vars['oauth_token'];
        $config->$platformId->token_secret = $vars['oauth_token_secret'];

        $output->writeln('Access Token: ' . $vars['oauth_token']);
        $output->writeln('Secret: ' . $vars['oauth_token_secret']);

        file_put_contents($this->getService('config.path'), json_encode($config, JSON_PRETTY_PRINT));

    }

    /**
     * @inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getService('config');

        if (!$input->getOption('platform_id')) {

            $input->setOption('platform_id', $this->getHelper('dialog')->ask($output, "Platform Id:"));
        }

        $platformConfig = new stdClass;

        if (isset($config->{$input->getOption('platform_id')})) {

            $platformConfig = $config->{$input->getOption('platform_id')};
        }

        if (!isset($platformConfig->base_url)) {

            $platformConfig->base_url = $this->getHelper('dialog')->ask($output, "Base Url:");
        }

        if (!isset($platformConfig->admin_url_front_name)) {

            $platformConfig->admin_url_front_name = $this->getHelper('dialog')->ask($output, "Admin Url Front Name (usually 'admin'):");
        }

        if (!isset($platformConfig->consumer_key)) {

            $platformConfig->consumer_key = $this->getHelper('dialog')->ask($output, "Consumer Key:");
        }

        if (!isset($platformConfig->consumer_secret)) {

            $platformConfig->consumer_secret = $this->getHelper('dialog')->ask($output, "Consumer Secret:");
        }

        $config->{$input->getOption('platform_id')} = $platformConfig;
        $this->getContainer()['config'] = $config;

        file_put_contents($this->getService('config.path'), json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * @param $platform
     * @param $path
     *
     * @return mixed
     */
    private function getResponse($platformId, $path)
    {
        /** @var Callable $clientFactory */
        $clientFactory = $this->getContainer()['client_factory'];
        /** @var Client $client */
        $client = $clientFactory($platformId);

        try {
            parse_str((string) $client->post($path)->getBody(), $vars);
        } catch (RequestException $e) {
            throw new RuntimeException((string) $e->getResponse()->getBody(), 0, $e);
        }

        return $vars;
    }
}
