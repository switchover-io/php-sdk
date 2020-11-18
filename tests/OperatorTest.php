<?php

namespace Switchover;

use PHPUnit\Framework\TestCase;
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

        $bag = new OperatorBag();

        $val = $bag->satisfies($condition, new Context());
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

        $bag = new OperatorBag();

        $context = new Context();
        $context->set('key01', 'aStringValue001');

        $val = $bag->satisfies($condition, $context);
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', '3');

        $this->assertTrue($bag->satisfies($condition, $context1));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', 2);

        $this->assertTrue($bag->satisfies($condition, $context1));

        $context2 = new Context();
        $context2->set('key01', 3);

        $this->assertTrue($bag->satisfies($condition, $context2));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', "red");

        $this->assertTrue($bag->satisfies($condition, $context1));

        $context2 = new Context();
        $context2->set('key01', "orange");

        $this->assertFalse($bag->satisfies($condition, $context2));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', "red");

        $this->assertFalse($bag->satisfies($condition, $context1));

        $context2 = new Context();
        $context2->set('key01', "orange");

        $this->assertTrue($bag->satisfies($condition, $context2));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', 1);

        $this->assertTrue($bag->satisfies($condition, $context1));

        $context2 = new Context();
        $context2->set('key01', 3);

        $this->assertFalse($bag->satisfies($condition, $context2));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', 1);

        $this->assertTrue($bag->satisfies($condition, $context1));

        $context2 = new Context();
        $context2->set('key01', 2);

        $this->assertTrue($bag->satisfies($condition, $context2));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', 'brandon.taylor@acme.com');

        $this->assertTrue($bag->satisfies($condition, $context1));
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

        $bag = new OperatorBag();

        $context1 = new Context();
        $context1->set('key01', 'brandon.taylor@acme.com');

        $this->assertFalse($bag->satisfies($condition, $context1));
    }
}
