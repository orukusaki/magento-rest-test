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
            ->addArgument('request', InputArgument::REQUIRED)
            ->addOption('platform', 'p', InputOption::VALUE_REQUIRED, 'Platform (from config.json)')
            ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'Method (default: GET)', 'GET');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $platform = $input->getOption('platform');

        /** @var Callable $clientFactory */
        $clientFactory = $this->getContainer()['client_factory'];
        /** @var Client $client */
        $client = $clientFactory($platform);

        try {
            $request = $client->createRequest($input->getOption('method'), $input->getArgument('request'));
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
        if (!$input->getOption('platform')) {

            $input->setOption('platform', $this->getHelper('dialog')->ask($output, "Platform Name:"));
        }

        if (!$input->getArgument('request')) {

            $input->setArgument('request', $this->getHelper('dialog')->ask($output, "Request:"));
        }
    }
}
