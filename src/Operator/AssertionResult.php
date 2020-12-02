<?php 

namespace Switchover\Operator; 

class AssertionResult {

    /** @var bool */
    public $isValid;

    /** @var mixed */
    public $rolloutValue; 

    function __construct(bool $isValid = false, $rolloutValue = null)
    {
        $this->isValid = $isValid;
        $this->rolloutValue = $rolloutValue;
    }
}