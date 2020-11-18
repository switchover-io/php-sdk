<?php 

namespace Switchover;

class ApiResponse {

    /**
     * Holds Last-Modified Header
     *
     * @var string
     */
    public $lastModified;


    /**
     * Api Payload (normally json decoded)
     *
     * @var array
     */
    public $payload;

    function __construct(string $lastModified = null, array $payload = null)
    {
        $this->lastModified = $lastModified;
        $this->payload = $payload;
    }

    

}