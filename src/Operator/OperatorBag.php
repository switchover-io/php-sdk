<?php

namespace Switchover\Operator;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Switchover\Context;
use Switchover\Evaluator;
use Switchover\Exceptions\EvaluationException;
use Switchover\Util\ConditionProperties;

interface OperatorInteface
{
    /**
     * Validates a conditon value against a context value (actual)
     *
     * @param mixed $conditionVal
     * @param mixed $actual
     * @return \Switchover\Operator\AssertionResult
     */
    function validate($conditionVal, $actual);
}

interface RolloutOperatorInterface
{
    /**
     * validates allocations against a calculated ratio from uuid and salt
     *
     * Returns a AssertResult which can hold an optional rollout value
     * 
     * @param array $allocations
     * @param string $uuid
     * @param string $salt
     * @return \Switchover\Operator\AssertionResult
     */
    function validate($allocations, $uuid, $salt);
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

class MultivariationRollout implements RolloutOperatorInterface 
{
    /** @var integer */
    private $buckets = 10000;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger; 

    function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    function validate($allocations, $uuid, $salt)
    {
        $bucket = (int)($this->hashRatio($uuid, $salt) * $this->buckets);

        $allocationBuckets = $this->allocationsToBucket($allocations);

        $variation = $this->getVariation($bucket, $allocationBuckets);
        if ($variation) {
            $allocationValue = array_key_exists('value', $variation) ? $variation['value'] : null;
            return new AssertionResult(true, $allocationValue);
        }
        return new AssertionResult(false);
    }

    private function getVariation($bucket, $allocations) 
    {
        $this->logger->debug('Get variation for {bucket}', ["bucket" => $bucket]);
        foreach($allocations as $allocation) {
            if ($bucket < $allocation['rangeEnd']) {
                return $allocation;
            }
        }
        return null;
    }

    private function hashRatio($identifier, $salt)
    {
        $hashCand = $identifier . '-' . $salt;

        $strHash = substr(md5($hashCand), 0, 6);
        $intHash = intval($strHash, 16);
        $split = $intHash / 0xFFFFFF;

        $this->logger->debug('Calculated split for {id}: {split}', 
                ['id' => $identifier, 'split' => $split]);

        return $split;
    }

    private function allocationsToBucket($allocations) {
        $allocationBuckets = [];
        $sum = 0;
        foreach($allocations as $allocation) {
            $last = $allocation['ratio'] * $this->buckets;
            $sum += $last;
            array_push($allocationBuckets, [
                "name" => $allocation["name"],
                "value" => $allocation["value"],
                "rangeEnd" => (int)$sum
            ]);
        }
        return $allocationBuckets;
    }
    

}



interface ConditionAssertion
{
    /**
     * Check condition againt given context
     *
     * @param array $condition
     * @param Context $context
     * @return \Switchover\Operator\AssertionResult
     */
    function satisfies(array $condition, Context $context, string $toggleName);
}


class OperatorBag implements ConditionAssertion
{

    /**
     * @var array
     */
    private $operators;

    /** 
     * @var \Psr\Log\LoggerInterface
     */
    private $logger; 

    function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->operators = [
            'equal' => new EqualTo(),
            'greater-than' => new GreaterThan(),
            'greater-than-equal' => new GreaterThanEqual(),
            'in-set' => new InSet(),
            'not-in-set' => new NotInSet(),
            'less-than' => new LessThan(),
            'less-than-equal' => new LessThanEqual(),
            'matches-regex' => new MatchesRegex(),
            'multivariation' => new MultivariationRollout($this->logger),
        ];
    }

    public function satisfies(array $condition, Context $context, string $toggleName)
    {
  
        $ctxValue = $context->get($condition[ConditionProperties::KEY]);

        //check if condition is a rollout condition and $context holds a uuid
        if ($this->isRolloutCondition($condition)) {
            $uuid = $context->get('uuid');
            if (!$uuid) {
                throw new EvaluationException('Rollout condition/option is set but no uuid is given!');
            }
            $rolloutOperator = $this->operators['multivariation'];

            return $rolloutOperator->validate($condition[ConditionProperties::ALLOCATIONS], $uuid, $toggleName);
        }

        if (!$ctxValue) {
            return new AssertionResult(false);
        }
        if (!$this->hasOperator($condition)) {
            return new AssertionResult(false);
        }

        $operator = $this->operators[$condition[ConditionProperties::OPERATOR][ConditionProperties::OPERATOR_NAME]];

        $validationValue = $operator->validate(
            $condition[ConditionProperties::OPERATOR][ConditionProperties::OPERATOR_VALUE],
            $ctxValue
        );
        return new AssertionResult($validationValue);
    }

    private function isRolloutCondition(array $condition) {
        return array_key_exists(ConditionProperties::ALLOCATIONS, $condition) &&
                $condition[ConditionProperties::NAME] === ConditionProperties::ROLLOUT_CONDITION;
    }

    private function hasOperator($condition)
    {
        return array_key_exists($condition[ConditionProperties::OPERATOR][ConditionProperties::OPERATOR_NAME], $this->operators);
    }
}
