# Switchover SDK for PHP

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

TBD


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

TBD



## Documentation

Learn more on the official documentation: <a href="https://support.switch-over.io/docs/quick-primer">Switchover Quickstart</a>







