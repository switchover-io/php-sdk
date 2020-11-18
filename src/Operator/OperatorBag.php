<?php

namespace Switchover\Operator;

use Switchover\Context;
use Switchover\Util\ConditionProperties;

interface OperatorInteface
{
    function validate($conditionVal, $actual);
}

class EqualTo implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return $conditionVal === $actual;
    }
}

class GreaterThan implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return floatval($actual) > floatval($conditionVal);
    }
}

class GreaterThanEqual implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return floatval($actual) >= floatval($conditionVal);
    }
}

class InSet implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return in_array($actual, $conditionVal);
    }
}

class NotInSet implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return !in_array($actual, $conditionVal);
    }
}

class LessThan implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return floatval($actual) < floatval($conditionVal);
    }
}

class LessThanEqual implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return floatval($actual) <= floatval($conditionVal);
    }
}

class MatchesRegex implements OperatorInteface
{
    function validate($conditionVal, $actual)
    {
        return (bool)preg_match('/' . $conditionVal . '/', $actual);
    }
}


interface ConditionAssertion
{
    /**
     * Check condition againt given context
     *
     * @param array $condition
     * @param Context $context
     * @return boolean
     */
    function satisfies(array $condition, Context $context);
}


class OperatorBag implements ConditionAssertion
{

    /**
     * @var array
     */
    private $operators;

    function __construct()
    {
        $this->operators = [
            'equal' => new EqualTo(),
            'greater-than' => new GreaterThan(),
            'greater-than-equal' => new GreaterThanEqual(),
            'in-set' => new InSet(),
            'not-in-set' => new NotInSet(),
            'less-than' => new LessThan(),
            'less-than-equal' => new LessThanEqual(),
            'matches-regex' => new MatchesRegex()
        ];
    }

    public function satisfies(array $condition, Context $context)
    {
        $ctxValue = $context->get($condition[ConditionProperties::KEY]);
        if (!$ctxValue) {
            return false;
        }
        if (!$this->hasOperator($condition)) {
            return false;
        }

        $operator = $this->operators[$condition[ConditionProperties::OPERATOR][ConditionProperties::OPERATOR_NAME]];

        return $operator->validate(
            $condition[ConditionProperties::OPERATOR][ConditionProperties::OPERATOR_VALUE],
            $ctxValue
        );
    }

    private function hasOperator($condition)
    {
        return array_key_exists($condition[ConditionProperties::OPERATOR][ConditionProperties::OPERATOR_NAME], $this->operators);
    }
}
