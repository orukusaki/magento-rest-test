<?php

namespace Orukusaki\MagentoRestTest;

use Exception;
use Cilex\Command\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
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
            ->addOption('http_verb', 'm', InputOption::VALUE_REQUIRED, 'HTTP Verb (default: GET)', 'GET')
            ->addOption('request_content', 'c', InputOption::VALUE_REQUIRED, 'HTTP Request Content')
            ->addOption('request_type', 't', InputOption::VALUE_REQUIRED, 'HTTP Request Content Type');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $this->getErrOutput($output);

        $platformId = $input->getOption('platform_id');

        /** @var Callable $clientFactory */
        $clientFactory = $this->getContainer()['client_factory'];
        /** @var Client $client */
        $client = $clientFactory($platformId);

        try {
            $request = $client->createRequest(
                $input->getOption('http_verb'),
                'api/rest/' .
                ltrim($input->getArgument('http_resource'), '/')
            );

            $errOutput->writeln('<info>Sending: ' . $request->getUrl() . '</info>');

            if ($input->getOption('request_content')) {

                $this->addRequestContent($input, $request);
                $errOutput->writeln('Request body: ' . $request->getBody(), OutputInterface::OUTPUT_RAW);
            }

            /** @var ResponseInterface $response */
            $response = $client->send($request);

            $output->writeLn($this->formatResponse($response));

        } catch (RequestException $e) {

            $errOutput->writeln('<error> Request failed: ' . (string) $e->getResponse()->getBody() . '</error>');

        } catch (Exception $e) {
            $errOutput->writeln('<error> Invalid request: ' . (string) $e->getMessage() . '</error>');
        }
    }

    /**
     * @inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $this->getErrOutput($output);

        if (!$input->getOption('platform_id')) {

            $input->setOption('platform_id', $this->getHelper('dialog')->ask($errOutput, "Platform Id:"));
        }

        if (!$input->getArgument('http_resource')) {

            $input->setArgument('http_resource', $this->getHelper('dialog')->ask($errOutput, "HTTP Resource:"));
        }
    }

    /**
     * Convert a shorthand HTTP Content-Type in to the valid request type
     *
     * @param $type
     * @return string
     */
    protected function parseContentType($type) {
        $types = [
            'json' => 'application/json',
            'xml' => 'application/xml'
        ];

        return (in_array($type, array_keys($types))) ? $types[$type] : (string) $type;
    }

    /**
     * Format the response
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

    /**
     * @param InputInterface   $input
     * @param RequestInterface $request
     *
     * @return Stream
     * @throws Exception
     */
    protected function addRequestContent(InputInterface $input, RequestInterface $request)
    {
        $type = $input->getOption('request_type');

        if (!is_string($type)) {
            throw new Exception('request_type (-t) is a required parameter when request_content (-c) is used');
        }

        $body = Stream::factory($input->getOption('request_content'));
        $request->setBody($body);
        $request->setHeader('Content-Type', $this->parseContentType($type));
    }

    /**
     * @param OutputInterface $output
     *
     * @return OutputInterface
     */
    private function getErrOutput(OutputInterface $output)
    {
        return $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
    }
}
