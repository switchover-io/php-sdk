<?php

namespace Switchover;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Switchover\Exceptions\EvaluationException;
use Switchover\Operator\OperatorBag;

class OperatorTest extends TestCase
{

    public function testEmptyContextShouldReturnFalse()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'equal',
                "value" => 'aValue002'
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $val = $bag->satisfies($condition, new Context(), '')->isValid;
        $this->assertFalse($val);
    }

    public function testEqual()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'equal',
                "value" => 'aStringValue001'
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context = new Context();
        $context->set('key01', 'aStringValue001');

        $val = $bag->satisfies($condition, $context, '')->isValid;
        $this->assertTrue($val);
    }

    public function testGreaterThan()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'greater-than',
                "value" => 2
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', '3');

        $this->assertTrue($bag->satisfies($condition, $context1,'')->isValid);
    }

    public function testGreaterThanEqual()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'greater-than-equal',
                "value" => 2
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', 2);

        $this->assertTrue($bag->satisfies($condition, $context1, '')->isValid);

        $context2 = new Context();
        $context2->set('key01', 3);

        $this->assertTrue($bag->satisfies($condition, $context2, '')->isValid);
    }

    public function testInSet()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'in-set',
                "value" => ["blue", "green", "red"]
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', "red");

        $this->assertTrue($bag->satisfies($condition, $context1, '')->isValid);

        $context2 = new Context();
        $context2->set('key01', "orange");

        $this->assertFalse($bag->satisfies($condition, $context2, '')->isValid);
    }

    public function testNotInSet()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'not-in-set',
                "value" => ["blue", "green", "red"]
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', "red");

        $this->assertFalse($bag->satisfies($condition, $context1, '')->isValid);

        $context2 = new Context();
        $context2->set('key01', "orange");

        $this->assertTrue($bag->satisfies($condition, $context2, '')->isValid);
    }


    public function testLessThan()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'less-than',
                "value" => 2
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', 1);

        $this->assertTrue($bag->satisfies($condition, $context1, '')->isValid);

        $context2 = new Context();
        $context2->set('key01', 3);

        $this->assertFalse($bag->satisfies($condition, $context2, '')->isValid);
    }



    public function testLessThanEqual()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'less-than-equal',
                "value" => 2
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', 1);

        $this->assertTrue($bag->satisfies($condition, $context1, '')->isValid);

        $context2 = new Context();
        $context2->set('key01', 2);

        $this->assertTrue($bag->satisfies($condition, $context2, '')->isValid);
    }

    public function testMatchesRegex()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'matches-regex',
                "value" => '@acme.com'
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', 'brandon.taylor@acme.com');

        $this->assertTrue($bag->satisfies($condition, $context1, '')->isValid);
    }

    public function testOperatorNotExistsShouldReturnFalse()
    {
        $condition = [
            "key" => "key01",
            "operator" => [
                "name" => 'fuzzy',
                "value" => '@acme.com'
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();
        $context1->set('key01', 'brandon.taylor@acme.com');

        $this->assertFalse($bag->satisfies($condition, $context1, '')->isValid);
    }

    public function testPercentualRolloutNoUUID() {

        $this->expectException(EvaluationException::class);
        $condition = [
            "key" => "percentual-rollout",
            "name" => "rollout-condition",
            "allocations" => [
                "name" => "bucketA",
                "ratio" => 0.5,
            ]
        ];

        $bag = new OperatorBag(new Logger('test'));

        $context1 = new Context();

        $bag->satisfies($condition, $context1, 'feature');        
    }

    public function testPercentualRollout() {

        $condition = [
            "key" => "percentual-rollout",
            "name" => "rollout-condition",
            "allocations" => [
                [
                "name" => "bucketA",
                "ratio" => 0.5,
                "value" => null
                ]
            ]
        ];

        $logger = new Logger('test', [new ErrorLogHandler()]);
        $bag = new OperatorBag($logger);

        $context1 = new Context(['uuid' => 1]);
        $this->assertFalse($bag->satisfies($condition, $context1, 'feature')->isValid);  

        $context2 = new Context(['uuid' => 2]);
        $this->assertTrue($bag->satisfies($condition, $context2, 'feature')->isValid);  
    }

    

    public function testAbSplitWithAllocationValue(){
        $condition = [
            "key" => "ab-split",
            "name" => "rollout-condition",
            "allocations" => [
                [
                "name" => "bucketA",
                "ratio" => 0.5,
                "value" => 1
                ],
                [
                    "name" => "bucketB",
                    "ratio" => 0.5,
                    "value" => 2
                ]
            ]
        ];

        $logger = new Logger('test', [new ErrorLogHandler()]);
        $bag = new OperatorBag($logger);

        $context1 = new Context(['uuid' => 1]);
        $result = $bag->satisfies($condition, $context1, 'feature');  
        $this->assertTrue($result->isValid);
        $this->assertEquals(2, $result->rolloutValue);

        $context2 = new Context(['uuid' => 2]);
        $result2 = $bag->satisfies($condition, $context2, 'feature'); 
        $this->assertTrue($result2->isValid);  
        $this->assertEquals(1, $result2->rolloutValue);

    } 
}
