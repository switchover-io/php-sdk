<?php

namespace Switchover;

use Exception;
use InvalidArgumentException;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use Switchover\Exceptions\CacheArgumentException;
use Switchover\Operator\OperatorBag;
use Switchover\Util\ToggleProperties;

class SwitchoverClient
{


    /** @var string */
    private $sdkKey;

    /** @var \Psr\SimpleCache\CacheInterface  */
    private $cache;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var FetcherInterface */
    private $fetcher;

    /** @var Evaluator */
    private $evaluator;

    /** @var string */
    private $cacheKey;

    /** @var string */
    private $cacheTime;

    function __construct(string $sdkKey, array $options = null)
    {

        $this->assertSdkKey($sdkKey);

        if (is_null($options)) {
            $options = [];
        }

        $httpOptions = [];
        if (array_key_exists('http', $options)) {
            $httpOptions = $options['http'];
        }

        $cacheTime = 60;
        if (array_key_exists('cache.time', $options)) {
            $cacheTime = $options['cache.time'] ?? 0;
            $this->assertCacheTime($cacheTime);
        }

        $cacheInstance = new KeyValueCache();
        if (array_key_exists('cache', $options)) {
            $cacheInstance = $options['cache'];
            $this->assertCacheType($cacheInstance);
        }


        $loggerInstance = new Logger('Switchover', [new ErrorLogHandler()]);
        if (array_key_exists('logger', $options)) {
            $loggerInstance = $options['logger'];
            $this->assertLoggerType($loggerInstance);
        }

        $this->sdkKey = $sdkKey;
        $this->cache = $cacheInstance;
        $this->logger = $loggerInstance;
        $this->evaluator = $this->createEvaluator($loggerInstance);
        $this->fetcher = $this->createFetcher($loggerInstance, $httpOptions);

        $this->cacheKey = 'switchover_' . $this->sdkKey . '_toggles_v2';
        $this->cacheTime = $cacheTime;
    }


    /**
     * Returns a toggle value by given namen.
     *
     * It will return the given default value:
     *  - When toggle is INACTIVE
     *  - When evalution fails
     *  - Toggle config is empty/not fetched
     *
     * Context can hold properties which want to be evaluated against conditions if you have any set.
     *
     * @param string $name
     * @param Context $context
     * @param mixed $defaultValue
     * @return mixed | $defaultValue
     */
    public function toggleValue(string $name, $defaultValue, Context $context = null)
    {
        $toggles = $this->doGetToggles();
        if (is_null($toggles)) {
            $this->logger->error('Toggle config is null, will return default value');
            return $defaultValue;
        }
        return $this->evaluateValue($toggles, $name, $context, $defaultValue);
    }

    private function evaluateValue(array $toggles, string $name, Context $context = null, $defaultValue) {
        try {
            return $this->evaluator->evaluate($toggles, $name, $context, $defaultValue);
        } catch(Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error('Will return default value');
        }
        return $defaultValue;
    }

    private function doGetToggles()
    {
        try {
            $cachedResponse = $this->cache->get($this->cacheKey);

            if (is_null($cachedResponse)) {
                //fetch new toggles
                $apiResponse = $this->fetcher->fetchAll($this->sdkKey);

                $this->cache->set($this->cacheKey, $apiResponse, $this->cacheTime);

                return $apiResponse->payload;
            }
            return $cachedResponse->payload;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return null;
    }


    /**
     * Returns a list of keys from loaded toggles
     *
     * @return array
     */
    public function getToggleKeys() {
        $toggles = $this->doGetToggles();
        if (is_null($toggles)) {
            return [];
        }
        return array_map(function($toggle) {
            return $toggle[ToggleProperties::NAME];
        }, $toggles);
    }

    /**
     *  Manually refreshes toggles. This will overwrite the internal cached item
     *
     * @return void
     */
    public function refresh() {
        try {
            $apiResponse = $this->fetcher->fetchAll($this->sdkKey);
            $this->cache->set($this->cacheKey, $apiResponse, $this->cacheTime);
        } catch(Exception $e) {
            $this->logger->error('Refreshing Failed! {message}', ['message' => $e->getMessage() ]);
        }
    }

    private function createEvaluator(LoggerInterface $logger)
    {
        return new Evaluator($logger, new OperatorBag());
    }

    private function createFetcher(LoggerInterface $logger, array $httpOptions)
    {
        return new HttpFetcher($logger, $httpOptions);
    }

    private function assertSdkKey($value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('No SDK-KEY provided');
        }
    }

    private function assertCacheTime($value)
    {
        if (!is_null($value) && !is_integer($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The option cache.time only accepts int and null but "%s" provided.',
                    gettype($value)
                )
            );
        }
    }

    private function assertCacheType($value)
    {
        if (!($value instanceof CacheInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The option cache must be a PSR-16 compliant cache instance, but "%s" provided.',
                    gettype($value)
                )
            );
        }
    }

    private function assertLoggerType($value)
    {
        if (!($value instanceof LoggerInterface)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The option logger must be from type Psr\Log\LoggerInterface, but "%s" provided.',
                    gettype($value)
                )
            );
        }
    }
}
