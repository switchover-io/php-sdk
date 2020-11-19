<?php

namespace Switchover;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Switchover\Exceptions\EvaluationException;
use Switchover\Operator\ConditionAssertion;
use Switchover\Util\ConditionProperties;
use Switchover\Util\StatusOption;
use Switchover\Util\StrategyOption;
use Switchover\Util\ToggleProperties;

use function PHPUnit\Framework\isEmpty;

class Evaluator {

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger; 

    /**
     * @var ConditionAssertion
     */
    private $conditionAssertion;

    function __construct(LoggerInterface $logger, ConditionAssertion $conditionAssertion)
    {
        $this->logger = $logger;    
        $this->conditionAssertion = $conditionAssertion;
    }

    public function evaluate(array $config, string $toggleKey, Context $context = null, $defaultValue) {
        $this->logger->debug('Evalutate config for toggle ' . $toggleKey);

        if (empty($config)) {
            $this->logger->error('Toggle config is empty! Did you wait for init? All toggles will return default value');
        }

        if (empty($toggleKey)) {
            throw new EvaluationException('Toggle Key is empty');
        }

        $toggle = $this->findByName($config, $toggleKey);

        $this->logger->debug("Found toggle with name $toggleKey");
        if ($toggle) {
            $status = (int)$toggle[ToggleProperties::STATUS];
            
            $this->logger->debug('Evaluate toggle with status: ' . $status);

             //check conditions on given context
             switch ($status) {
                case StatusOption::ACTIVE:
                    return $this->evaluateOnActive($toggle, $context, $defaultValue); 
                case StatusOption::INACTIVE:
                    return $defaultValue;
            }
        }
        $this->logger->error('Toggle with name ' . $toggleKey . ' not found! Return default value');

        return $defaultValue;
    }

    private function findByName(array $config, $key) {
        $filtered = array_filter($config, function($k, $v) use ($key) {
            return $k[ToggleProperties::NAME] === $key;
        }, ARRAY_FILTER_USE_BOTH);
        return current($filtered);
    }

    private function evaluateOnActive(array $toggle, Context $context = null, $defaultValue) {
        if ($this->hasConditions($toggle)) {
            return $this->evaluateWithConditions($toggle, $context, $defaultValue);
        }
        return $toggle[ToggleProperties::VALUE];
    }

    private function hasConditions(array $toggle) {
        //condtions exits and is not empty
        return array_key_exists(ToggleProperties::CONDITIONS, $toggle) && 
                        !empty($toggle[ToggleProperties::CONDITIONS]);
    }

    private function evaluateWithConditions(array $toggle, Context $context = null, $defaultValue) {
        $this->logger->debug('Evaluate toggle with conditions');

        if (empty($context) && $this->hasConditions($toggle) ) {
            return $defaultValue;
        }

        $strategy = (int)$toggle[ToggleProperties::STRATEGY];

        $this->logger->debug('Evaluate toggle with strategy: ' . $strategy);

        switch($strategy) {
            case StrategyOption::STRATEGY_ALL: 
                return $this->evaluateAll($toggle, $context) ? $toggle[ToggleProperties::VALUE] : $defaultValue;
            case StrategyOption::STRATEGY_ATLEASTONE: 
                return $this->evaluateAtLeastOne($toggle, $context) ? $toggle[ToggleProperties::VALUE] : $defaultValue;
            case StrategyOption::STRATEGY_MAJORITY: 
                return $this->evaluateMajority($toggle, $context) ? $toggle[ToggleProperties::VALUE] : $defaultValue;
        }
        throw new EvaluationException('No toggle.strategy given!');
    }

    private function evaluateAll(array $toggle, Context $context) {
        $this->logger->debug('All toggle conditions have to be satisfied');

        foreach($toggle[ToggleProperties::CONDITIONS] as $condition) {
            if (!$this->conditionAssertion->satisfies($condition, $context)) {
                $this->logger->debug('Condition ' . $condition[ConditionProperties::KEY] . ' was not satisfied');       
                $this->logger->debug(json_encode($condition));
                return false;
            }
        }
        $this->logger->debug('All conditions satisfied');
        return true;
    }

    private function evaluateAtLeastOne(array $toggle, Context $context) {
        $this->logger->debug('At least one condition has to be satisfied');

        foreach($toggle[ToggleProperties::CONDITIONS] as $condition) {
            if ($this->conditionAssertion->satisfies($condition, $context)) {
                $this->logger->debug('Condition ' . $condition[ConditionProperties::KEY] . ' was not satisfied');       
                return true;
            }
        }

        $this->logger->debug('No condition satisfied');
        return false;
    }

    private function evaluateMajority(array $toggle, Context $context) {
        $this->logger->debug('Majority of conditions has to be satisfied');

        $hit = 0;
        $miss = 0;
        foreach($toggle[ToggleProperties::CONDITIONS] as $condition) {
            if ($this->conditionAssertion->satisfies($condition, $context)) {
                $this->logger->debug('Condition ' . $condition[ConditionProperties::KEY] . ' was satisfied by given context');    
                $hit++;
            } else {
                $miss++;
            }
        }

        return $hit > $miss;
    }
}