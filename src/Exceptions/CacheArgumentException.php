<?php 

namespace Switchover\Exceptions;

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;
use InvalidArgumentException;

class CacheArgumentException extends InvalidArgumentException implements InvalidArgumentExceptionInterface
{

}