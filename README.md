# Utopia Analytics

[![Build Status](https://travis-ci.com/utopia-php/system.svg?branch=main)](https://travis-ci.com/utopia-php/analytics)
![Total Downloads](https://img.shields.io/packagist/dt/utopia-php/analytics.svg)
[![Discord](https://img.shields.io/discord/564160730845151244?label=discord)](https://appwrite.io/discord)

Utopia Analytics is a simple and lite library to send information about events or pageviews to Google Analytics. This library is aiming to be as simple and easy to learn and use. This library is maintained by the [Appwrite team](https://appwrite.io).

Although this library is part of the [Utopia Framework](https://github.com/utopia-php/framework) project it is dependency free and can be used as standalone with any other PHP project or framework.

## Getting Started

Install using composer:
```bash
composer require utopia-php/analytics
```

Init in your application:
```php
<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Utopia\Analytics\GoogleAnalytics;

$ga = new GoogleAnalytics("UA-XXXXXXXXX-X", "CLIENT-ID");

// Sends a pageview for `appwrite.io/docs/installation`.
$ga->createPageView("appwrite.io", "/docs/installation");

// Sends an event indicating an installation.
$ga->createEvent("Installation", "setup.cli");
// Sends an event indicating that the fall campaign promotional video was played.
$ga->createEvent("Videos", "play", "Fall Campaign");

```

## System Requirements

Utopia Framework requires PHP 7.4 or later. We recommend using the latest PHP version whenever possible.

## Authors

**Eldad Fux**

+ [https://twitter.com/eldadfux](https://twitter.com/eldadfux)
+ [https://github.com/eldadfux](https://github.com/eldadfux)

**Torsten Dittmann**

+ [https://twitter.com/dittmanntorsten](https://twitter.com/dittmanntorsten)
+ [https://github.com/torstendittmann](https://github.com/torstendittmann)

## Copyright and license

The MIT License (MIT) [http://www.opensource.org/licenses/mit-license.php](http://www.opensource.org/licenses/mit-license.php)
