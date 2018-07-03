<?php

use Mettleworks\DeskComExporter\DeskComExporter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testFetchCases()
    {
        $exporter = $this->getMockedClient();

        $counter = 0;

        $exporter->fetchCases(function($cases) use(&$counter)
        {
            $counter += count($cases['_embedded']['entries']);
        });

        $this->assertTrue($counter === 12, 'Expected 12 received ' . $counter);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testFetchCustomers()
    {
        $exporter = $this->getMockedClient();

        $counter = 0;

        $exporter->fetchCustomers(function($cases) use(&$counter)
        {
            $counter += count($cases['_embedded']['entries']);
        });

        $this->assertTrue($counter === 12, 'Expected 12 received ' . $counter);
    }

    /**
     * @return DeskComExporter
     */
    protected function getMockedClient()
    {
        $mock = new MockHandler([
            new Response(200, [], $this->getFullResponse()),
            new Response(200, [], $this->getPartialResponse()),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $exporter = new DeskComExporter($client);
        $exporter->changeDefinedPageLimit(10);

        return $exporter;
    }

    /**
     * @return bool|string
     */
    protected function getFullResponse()
    {
        return file_get_contents(__DIR__ . '/files/response10.json');
    }

    /**
     * @return bool|string
     */
    protected function getPartialResponse()
    {
        return file_get_contents(__DIR__ . '/files/response2.json');
    }
}