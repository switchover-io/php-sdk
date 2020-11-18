<?php

namespace Switchover;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Switchover\Exceptions\FetchException;
use Switchover\Util\StatusOption;
use Switchover\Util\StrategyOption;

class FetcherTest extends TestCase
{

    private $toggleConfig = array(
        [
            "name" => "toggle-001",
            "status" => StatusOption::ACTIVE,
            "value" => true,
            "strategy" => StrategyOption::STRATEGY_MAJORITY
        ]
    );


    public function testFetchAllReplies200()
    {

        $mock = new MockHandler([
            new Response(
                200,
                ['Last-Modified' => 'Tue, 17 Nov 2020 21:52:43 GMT'],
                json_encode($this->toggleConfig)
            )
        ]);

        $stack = HandlerStack::create($mock);

        $logger = new Logger('Test');
        //$logger->pushHandler(new StreamHandler('test.log', 'debug'));

        $fetcher = new HttpFetcher($logger, ['handler' => $stack]);

        $apiResponse = $fetcher->fetchAll('SDK_KEY');

        $this->assertNotNull($apiResponse);
        $this->assertEquals('Tue, 17 Nov 2020 21:52:43 GMT', $apiResponse->lastModified);
        $this->assertEquals('toggle-001', $apiResponse->payload[0]['name']);
    }

    public function testFetchAllReplies304()
    {

        $mock = new MockHandler([
            new Response(
                200,
                ['Last-Modified' => 'Tue, 17 Nov 2020 21:52:43 GMT'],
                json_encode($this->toggleConfig)
            ),
            new Response(
                304,
                ['Last-Modified' => 'Tue, 17 Nov 2020 21:52:43 GMT'],
                json_encode($this->toggleConfig)
            )
        ]);

        $stack = HandlerStack::create($mock);

        $logger = new Logger('Test');

        $fetcher = new HttpFetcher($logger, ['handler' => $stack]);

        $response1 = $fetcher->fetchAll('SDK_KEY');

        $response2 = $fetcher->fetchAll('SDK_KEY', $response1->lastModified);

        $this->assertEquals($response1->lastModified, $response2->lastModified);
    }

    public function testFetchAllReplies404()
    {

        $this->expectException(FetchException::class);

        $mock = new MockHandler([
            new Response(404),
        ]);


        $stack = HandlerStack::create($mock);

        $logger = new Logger('Test');

        $fetcher = new HttpFetcher($logger, ['handler' => $stack]);
        $fetcher->fetchAll('SDK_KEY');
    }


    public function testFetchInvalidJson()
    {
        $this->expectException(FetchException::class);

        $mock = new MockHandler([
            new Response(200, ['Last-Modified' => 'Tue, 17 Nov 2020 21:52:43 GMT'], "{ 'invalid' : 'json'")
        ]);
        $stack = HandlerStack::create($mock);

        $logger = new Logger('Test');

        $fetcher = new HttpFetcher($logger, ['handler' => $stack]);
        $fetcher->fetchAll('SDK_KEY');
    }

    public function testFetchWithoutLastModified()
    {
        $mock = new MockHandler([
            new Response(200, ['Some-Other' => 'Foo'], json_encode($this->toggleConfig))
        ]);
        $stack = HandlerStack::create($mock);

        $logger = new Logger('Test');

        $fetcher = new HttpFetcher($logger, ['handler' => $stack]);
        $apiResponse = $fetcher->fetchAll('SDK_KEY');
        $this->assertEmpty($apiResponse->lastModified);
    }

    public function testFetch204ReturnsNull()
    {
        $mock = new MockHandler([
            new Response(204, ['Some-Other' => 'Foo'], null)
        ]);

        $stack = HandlerStack::create($mock);

        $stack = HandlerStack::create($mock);

        $logger = new Logger('Test');

        $fetcher = new HttpFetcher($logger, ['handler' => $stack]);
        $apiResponse = $fetcher->fetchAll('SDK_KEY');
        $this->assertNull($apiResponse);
    }
}
