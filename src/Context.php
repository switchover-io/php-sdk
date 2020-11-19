<?php

namespace Switchover;

class Context {

    private $collection = array();


    function __construct(array $values = null)
    {
        if (!is_null($values)) {
            $this->collection = array_merge($this->collection, $values);
        }
    }

    public function get($key) {
        if (!array_key_exists($key, $this->collection)) {
            return null;
        }
        return $this->collection[$key];
    }

    public function set($key, $value) {
        $this->collection[$key] = $value;
        return $this;
    }
}