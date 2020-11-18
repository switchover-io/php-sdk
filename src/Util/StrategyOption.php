<?php 

namespace Switchover\Util;

/**
 * @codeCoverageIgnore
 */
abstract class StrategyOption 
{
    const STRATEGY_ATLEASTONE = 1;
    const STRATEGY_MAJORITY = 2;
    const STRATEGY_ALL = 3;    
}