<?php

namespace Switchover;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use stdClass;
use Switchover\Util\StatusOption;
use Switchover\Util\StrategyOption;

class ClientTest extends TestCase
{

    function testNewClientCheckOptions()
    {

        $client = new SwitchoverClient('SDK_KEY', [
            'cache.time' => 10,
            'http' => [
                'timeout' => 20.0
            ]
        ]);

        $this->assertNotNull($client);
    }

    function testWrongCacheInstance()
    {

        $this->expectException(\InvalidArgumentException::class);

        $client = new SwitchoverClient('SDK_KEY', [
            'cache.time' => 10,
            'cache' => new class ()
            {
            }
        ]);
    }

    function testSdkKeyNotGiven()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new SwitchoverClient('');
    }

    function testWrongCacheTimeType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new SwitchoverClient('SDK_KEY', ['cache.time' => 3.2]);
    }

    function testWrongLoggerType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = new SwitchoverClient('SDK_KEY', ['logger' => new class ()
        {
        }]);
    }


    private $toggleConfig = array(
        [
            "name" => "toggle-001",
            "status" => StatusOption::ACTIVE,
            "value" => true,
            "strategy" => StrategyOption::STRATEGY_ALL,
            "conditions" => array(
                [
                    "key" => "key01",
                    "operator" => [
                        "name" => 'equal',
                        "value" => 'aValue002'
                    ]
                ],
                [
                    "key" => "key02",
                    "operator" => [
                        "name" => 'equal',
                        "value" => 'some_OtherValue'
                    ]
                ],
            )
        ],
        [
            "name" => "toggle-002",
            "status" => StatusOption::ACTIVE,
            "value" => 0.2,
            "strategy" => StrategyOption::STRATEGY_ALL,
        ],
        [
            "name" => "toggle-funny",
            "status" => StatusOption::INACTIVE,
            "value" => 12,
            "strategy" => StrategyOption::STRATEGY_ALL,
        ]
    );


    function testToggleValue()
    {
        $mock = new MockHandler([
            new Response(200, ['Some-Other' => 'Foo'], json_encode($this->toggleConfig))
        ]);
        $stack = HandlerStack::create($mock);

        $client = new SwitchoverClient('SDK_KEY', 
            ['http' => ['handler' => $stack]]);

        $value = $client->toggleValue("toggle-002", 0.5);

        $this->assertEquals(0.2, $value);

        $toggle1value = $client->toggleValue("toggle-001", false);

        $this->assertEquals(false, $toggle1value);
    }

    function testToggleWithResponseException() {
        $mock = new MockHandler([
            new Response(500, ['Some-Other' => 'Foo'], '')
        ]);
        $stack = HandlerStack::create($mock);

        $client = new SwitchoverClient('SDK_KEY', 
            ['http' => ['handler' => $stack]]);

        $value = $client->toggleValue("toggle-002", 0.5);

        $this->assertEquals(0.5, $value);
    }

    function testToggleWithNoName() {
        $mock = new MockHandler([
            new Response(200, ['Some-Other' => 'Foo'], json_encode($this->toggleConfig))
        ]);
        $stack = HandlerStack::create($mock);

        $client = new SwitchoverClient('SDK_KEY', 
            ['http' => ['handler' => $stack]]);

        $value = $client->toggleValue("", false);

        $this->assertFalse($value);
    }

    function testRefresh() {
        $config1 = array([
                "name" => "toggle-001",
                "status" => StatusOption::ACTIVE,
                "value" => 2,
                "strategy" => StrategyOption::STRATEGY_ALL]);

                $config2 = array([
                    "name" => "toggle-001",
                    "status" => StatusOption::ACTIVE,
                    "value" => 4,
                    "strategy" => StrategyOption::STRATEGY_ALL]);

        $mock = new MockHandler([
            new Response(200, ['Some-Other' => 'Foo'], json_encode($config1)),
            new Response(200, ['Some-Other' => 'Foo'], json_encode($config2)),
            new Response(500)
        ]);
        $stack = HandlerStack::create($mock);

        $client = new SwitchoverClient('SDK_KEY',  ['http' => ['handler' => $stack]]);

        $value = $client->toggleValue("toggle-001", 0);

        $this->assertEquals(2, $value);

        $client->refresh();
        $refreshValue = $client->toggleValue("toggle-001", 0);

        $this->assertEquals(4, $refreshValue);

        $client->refresh();
        $this->assertEquals(4, $client->toggleValue("toggle-001", 0));
    }

    function testGetToggleKeys() {
        $mock = new MockHandler([
            new Response(200, ['Some-Other' => 'Foo'], json_encode($this->toggleConfig))
        ]);
        $stack = HandlerStack::create($mock);

        $client = new SwitchoverClient('SDK_KEY',  ['http' => ['handler' => $stack]]);

        $keys = $client->getToggleKeys();

        $this->assertCount(3, $keys);
    }
}
