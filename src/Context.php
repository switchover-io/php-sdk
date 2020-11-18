<?php

namespace Switchover;

class Context {

    private $collection = array();

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