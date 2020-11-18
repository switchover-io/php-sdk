<?php 

namespace Switchover\Exceptions;

use Psr\SimpleCache\CacheException as CacheExceptionInterface;
use Exception;

class CacheException extends Exception implements CacheExceptionInterface
{

}