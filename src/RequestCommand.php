<?php

namespace Orukusaki\MagentoRestTest;

use Cilex\Command\Command;
use GuzzleHttp\Client;
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
            ->addOption('http_verb', 'm', InputOption::VALUE_OPTIONAL, 'HTTP Verb (default: GET)', 'GET');
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

            /** @var ResponseInterface $response */
            $response = $client->send($request);

            $output->writeln(json_encode($response->json(), JSON_PRETTY_PRINT));

        } catch (RequestException $e) {

            $output->writeln('Request failed: ' . (string) $e->getResponse()->getBody());

            throw $e;
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
}
