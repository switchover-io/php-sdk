<?php 

namespace Switchover;

interface FetcherInterface
{
    /**
     * Fetches toggles from server with given SDK KEy
     *
     * @param string $sdkKey
     * @param string $lastModified
     * @return Switchover\ApiResponse
     */
    public function fetchAll(string $sdkKey, string $lastModified = null);
}