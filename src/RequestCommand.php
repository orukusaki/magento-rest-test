<?php

namespace Orukusaki\MagentoRestTest;

use Exception;
use Cilex\Command\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RequestCommand
 * @package Orukusaki\MagentoRestTest
 */
class RequestCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('request')
            ->setDescription('Perform a REST request (after authorising)')
            ->addArgument('http_resource', InputArgument::REQUIRED, 'HTTP Resource')
            ->addOption('platform_id', 'p', InputOption::VALUE_REQUIRED, 'Platform Id (from config.json)')
            ->addOption('http_verb', 'm', InputOption::VALUE_OPTIONAL, 'HTTP Verb (default: GET)', 'GET')
            ->addOption('request_content', 'c', InputOption::VALUE_OPTIONAL, 'HTTP Request Content')
            ->addOption('request_type', 't', InputOption::VALUE_OPTIONAL, 'HTTP Request Content Type');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platformId = $input->getOption('platform_id');

        /** @var Callable $clientFactory */
        $clientFactory = $this->getContainer()['client_factory'];
        /** @var Client $client */
        $client = $clientFactory($platformId);

        try {
            $request = $client->createRequest($input->getOption('http_verb'), 'api/rest/' . ltrim($input->getArgument('http_resource'), '/'));
            $output->writeln('Sending: ' . $request->getUrl());

            if ($input->getOption('request_content')) {

                if (!is_string($input->getOption('request_type'))) {
                    throw new Exception('request_type (-t) is a required parameter when request_content (-c) is used');
                }

                $body = Stream::factory($input->getOption('request_content'));
                $request->setBody($body);
                $request->setHeader('Content-Type', $this->parseContentType($input->getOption('request_type')));
                $output->writeln('Request body: ' . $body);
            }

            /** @var ResponseInterface $response */
            $response = $client->send($request);

            $output->writeLn($this->formatResponse($response));

        } catch (RequestException $e) {

            $output->writeln('Request failed: ' . (string) $e->getResponse()->getBody());

            throw $e;
        } catch (Exception $e) {
            $output->writeln('Invalid request: ' . (string) $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('platform_id')) {

            $input->setOption('platform_id', $this->getHelper('dialog')->ask($output, "Platform Id:"));
        }

        if (!$input->getArgument('http_resource')) {

            $input->setArgument('http_resource', $this->getHelper('dialog')->ask($output, "HTTP Resource:"));
        }
    }

    /**
     * Convert a shorthand HTTP Content-Type in to the valid request type
     *
     * @param $type
     * @return string
     */
    protected function parseContentType($type)
    {
        $types = array(
            'json' => 'application/json',
            'xml' => 'application/xml'
        );

        return (in_array($type, array_keys($types))) ? $types[$type] : (string) $type;
    }

    /**
     * Format the response based on which http verb you are using
     * When you create an object through the rest API, Magento doesn't return a valid response and instead sets a
     * "location" header
     *
     * @param $response
     * @return string
     */
    protected function formatResponse($response)
    {
        if ($response->getHeader('location') !== "") {
            return $response->getHeader('location');
        }

        return json_encode($response->json(), JSON_PRETTY_PRINT);
    }
}
