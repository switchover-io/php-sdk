<?php

namespace Switchover;

use Psr\SimpleCache\CacheInterface;
use Switchover\Exceptions\CacheArgumentException;

class KeyValueCache implements CacheInterface
{

    /** @var array */
    private $cache = array();


    function get($key, $default = null)
    {
        if (!$this->has($key, $this->cache)) {
            return $default;
        }

        $data = $this->cache[$key];
        if (!empty($data)) {
            if ($this->isExpired($data['ttl'], $data['timestamp'])) {
                $this->delete($key);
                return $default;
            }
            $default = $data['value'];
        }

        return $default;
    }

    function set($key, $value, $ttl = null)
    {
        $this->assertString($key);
        $this->assertTtlAsIntSeconds($ttl);

        if (is_null($ttl)) {
            $ttl = 0;
        }

        $timestamp = time();

        $data = [
            'ttl' => $ttl,
            'timestamp' => $timestamp,
            'value' => $value
        ];

        $this->cache[$key] = $data;

        return true;
    }

    function has($key)
    {
        return array_key_exists($key, $this->cache);
    }

    function getMultiple($keys, $default = null)
    {
        //$this->assertIterable($keys);
    }

    function setMultiple($values, $ttl = null)
    {
        //nothing
    }

    function delete($key)
    {
        $this->assertString($key);

        unset($this->cache[$key]);
        
        return true;
    }

    function clear()
    {
        unset($this->cache);
        $this->cache = array();

        return true;
    }

    function deleteMultiple($keys)
    {
        //nothing
    }

    private function isExpired($ttl, $timestamp)
    {
        $now = time();

        // If $ttl equal to 0 means that never expires.
        if (empty($ttl)) {
            return false;
        } elseif ($now - $timestamp < $ttl) {
            return false;
        }

        return true;
    }

    private function assertTtlAsIntSeconds($ttl)
    {
        if (!is_null($ttl) && !is_integer($ttl)) {
            throw new CacheArgumentException(
                sprintf(
                    'The TTL only accetps int, null and DateInterval instance, but "%s" provided.',
                    gettype($ttl)
                )
            );
        }
    }

    /**
     * Check if string
     *
     * @param string $value
     * @return void
     */
    private function assertString($value)
    {
        if (!is_string($value)) {
            throw new CacheArgumentException(
                sprintf(
                    'The type of value must be string, but "%s" provided.',
                    gettype($value)
                )
            );
        }
    }

}
