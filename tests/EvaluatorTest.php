<?php

declare(strict_types=1);

namespace Switchover;

use Exception;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Switchover\Exceptions\EvaluationException;
use Switchover\Operator\ConditionAssertion;
use Switchover\Operator\OperatorBag;
use Switchover\Util\StatusOption;
use Switchover\Util\StrategyOption;

class EvaluatorTest extends TestCase
{

    public function testEmptyToggleKeyShouldThrowException()
    {
        $this->expectException(EvaluationException::class);

        $logger = new Logger('Switchover');

        /** @var ConditionAssertion */
        $mock = $this->createMock(OperatorBag::class);

        $evaluator = new Evaluator($logger,  $mock);

        $config = array(
            array('name' => 'my-toggle-01', 'status' => 1)
        );

        $evaluator->evaluate($config, '', null, false);
    }


    public function testEmptyConfigReturnsDefaultValue()
    {
        $logger = new Logger('Switchover');

        /** @var ConditionAssertion */
        $mock = $this->createMock(ConditionAssertion::class);

        $evaluator = new Evaluator($logger, $mock);

        $config = array();

        $val = $evaluator->evaluate($config, 'my-toggle-001', null, false);

        $this->assertFalse($val);
    }

    public function testToggleReturnsTrueWithoutConditions()
    {
        $config = array([
            "name" => "toggle-001",
            "status" => StatusOption::ACTIVE,
            "value" => true,
            "strategy" => StrategyOption::STRATEGY_MAJORITY,
            "conditions" => []
        ]);
        $logger = new Logger('test');

        /** @var ConditionAssertion */
        $mock = $this->createMock(ConditionAssertion::class);

        $evaluator = new Evaluator($logger, $mock);
        $val = $evaluator->evaluate($config, 'toggle-001', null, false);
        $this->assertTrue($val);
    }

    public function testEvaluationAlwaysInactive()
    {
        $config = array([
            "name" => "toggle-001",
            "status" => StatusOption::INACTIVE,
            "value" => true,
            "strategy" => StrategyOption::STRATEGY_MAJORITY
        ]);
        $logger = new Logger('test');

        /** @var ConditionAssertion */
        $mock = $this->createMock(ConditionAssertion::class);

        $evaluator = new Evaluator($logger, $mock);
        $val = $evaluator->evaluate($config, 'toggle-001', null, false);
        $this->assertFalse($val);
    }

    public function testEvaluationStrategyAllConditionsTrue()
    {
        $config = array([
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
        ]);

        $context = new Context();
        $context->set('key01', 'aValue002');
        $context->set('key02', 'some_OtherValue');

        $logger = new Logger('test');

        /** @var ConditionAssertion */
        $mock = new class implements ConditionAssertion
        {
            function satisfies(array $condition, Context $context)
            {
                return true;
            }
        };

        //$logger->pushHandler(new StreamHandler('test.log', 'debug'));
        $evaluator = new Evaluator($logger, $mock);
        $val = $evaluator->evaluate($config, 'toggle-001', $context, false);
        $this->assertTrue($val);
    }

    public function testEvaluationStrategyAllConditionsFalse()
    {
        $config = array([
            "name" => "toggle-001",
            "status" => StatusOption::ACTIVE,
            "value" => false,
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
        ]);

        $context = new Context();
        $context->set('key01', 'aValue002');
        $context->set('key02', 'some_OtherValue');

        $logger = new Logger('test');

        /** @var ConditionAssertion */
        $mock = new class implements ConditionAssertion
        {
            function satisfies(array $condition, Context $context)
            {
                return false;
            }
        };

        //$logger->pushHandler(new StreamHandler('test.log', 'debug'));
        $evaluator = new Evaluator($logger, $mock);
        $val = $evaluator->evaluate($config, 'toggle-001', $context, true);
        $this->assertTrue($val);
    }

    public function testEvaluationStrategyAtLeastOnConditionTrue()
    {
        $config = array([
            "name" => "toggle-001",
            "status" => StatusOption::ACTIVE,
            "value" => true,
            "strategy" => StrategyOption::STRATEGY_ATLEASTONE,
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
        ]);

        $context = new Context();
        $context->set('key01', 'aValue002');
        $context->set('key02', 'very_different_value');


        $logger = new Logger('test');

        /** @var ConditionAssertion */
        $bag = new OperatorBag();

        $evaluator = new Evaluator($logger, $bag);
        $val = $evaluator->evaluate($config, 'toggle-001', $context, false);
        $this->assertTrue($val);
    }


    public function testEvaluationStrategyMajorityConditionTrue()
    {
        $config = array([
            "name" => "toggle-001",
            "status" => StatusOption::ACTIVE,
            "value" => true,
            "strategy" => StrategyOption::STRATEGY_MAJORITY,
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
                [
                    "key" => "key03",
                    "operator" => [
                        "name" => 'equal',
                        "value" => 'aValue004'
                    ]
                ],
            )
        ]);

        $context = new Context();
        $context->set('key01', 'aValue002');
        $context->set('key02', 'very_different_value');
        $context->set('key03', 'aValue004');


        $logger = new Logger('test');

        /** @var ConditionAssertion */
        $bag = new OperatorBag();

        $evaluator = new Evaluator($logger, $bag);
        $val = $evaluator->evaluate($config, 'toggle-001', $context, false);
        $this->assertTrue($val);
    }
}
