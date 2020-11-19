# Switchover SDK for PHP

![CI](https://github.com/switchover-io/php-sdk/workflows/CI/badge.svg)
[![codecov](https://codecov.io/gh/switchover-io/php-sdk/branch/main/graph/badge.svg?token=eoSgEfaI5J)](https://codecov.io/gh/switchover-io/php-sdk)
[![CodeFactor](https://www.codefactor.io/repository/github/switchover-io/php-sdk/badge)](https://www.codefactor.io/repository/github/switchover-io/php-sdk)
## Switchover

Switchover is a Software-As-A-Service for managing feature toggles (aka switches, flags or feature flips) in your application. Use it for Continous Integration, Continous Delivery, A/B-Testing, Canary Releases, Experementing and everything else you can think of.

__Note:__
Use this SDK for PHP Projects

## Getting Started


### Install

Via composer:

```bash
composer require switchover/php-sdk
```

### For Laravel Users

Use our Laravel Package to get up and running quickly with Switchover in your Laravel App:
https://github.com/switchover-io/laravel-integration


### Initialize client

You will find your SDK Key on the environment page. Copy it and use it to initialize the client:

Basic usage:

```php
// create a client, per default the client will cache the toggles 60 seconds
$client = new SwitchoverClient('<SDK-KEY');

//get some toggle values
$featureValue = $client->toggleValue('<TOGGLE-NAME>', false);

//or with context if you have specific (user) conditions
$context = new Context();
$context->set('email', 'brandon.taylor@acme.com');

$isFeatureVisible = $client->toggleValue('<OTHER-FLAG>', false, $context);

if ($isFeatureVisible) {
    // ...do something
}
```

### Options

It's possible to pass numerous options to the client:

|Option|Value|
|:-----|:----|
| `cache.time` | Sets time in seconds before the internal cache becomes stale and will be refreshed (TTL). Default is 60 seconds. The value 0 will keep the cache forever. |
| `logger` | Possibility to provide you own logger instance (PSR-7). |
| `cache` | Option to set your cache instance (e.g. for redis). Expects a PSR-16 compliant instance. |
| `http` | The client uses `guzzlehttp/guzzle` for http requests. You can pass an array of options to the Guzzle Http Client. |

Example

```php
$client = new SwitchoverClient('<SDK-KEY', [
    'cache.time' => 10,
    'http' => [
        'timeout' => '10',
        'proxy' => 'http://proxy.tld'
    ]
]);
```



## Documentation

Learn more on the official documentation: <a href="https://support.switch-over.io/docs/quick-primer">Switchover Quickstart</a>







