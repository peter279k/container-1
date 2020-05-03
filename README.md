# selective/container

[![Latest Version on Packagist](https://img.shields.io/github/release/selective-php/container.svg?style=flat-square)](https://packagist.org/packages/selective/container)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://github.com/selective-php/container/workflows/PHP/badge.svg)](https://github.com/selective-php/container/actions)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/selective-php/container.svg?style=flat-square)](https://scrutinizer-ci.com/g/selective-php/container/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/quality/g/selective-php/container.svg?style=flat-square)](https://scrutinizer-ci.com/g/selective-php/container/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/selective/container.svg?style=flat-square)](https://packagist.org/packages/selective/container/stats)

## Description

A simple PSR-11 container implementation with **autowiring**.

## Requirements

 * PHP 7.2+

## Installation

```sh
composer require selective/container
```

## Usage

### Use dependency injection

The container is able to automatically create and inject dependencies for you. This is called "autowiring".

```php
<?php

use App\Service\MyService;
use Selective\Container\Container;
use Selective\Container\Resolver\ConstructorResolver;

$container = new Container();

// Enable autowiring
$container->addResolver(new ConstructorResolver($container));

$service = $container->get(MyService::class);
```

### Factories

On top of autowiring, you can use a factories (closures) to define injections.

```php
<?php

use App\Service\MyService;
use Selective\Container\Container;
use Selective\Container\Resolver\ConstructorResolver;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

$container = new Container();

// Add definition
$container->factory(MyService::class, function (ContainerInterface $container) {
    return new MyService($container->get(LoggerInterface::class));
});
```

**Please note:** It's not supported to replace or extend an existing definition to prevent **unwanted side affects**.

### Service providers

Service providers give the benefit of organising your container 
definitions along with an increase in performance for larger applications 
as definitions registered within a service provider are lazily registered 
at the point where a service is retrieved.

To build a service provider create a invokable class and 
return the definitons (factories) you would like to register.

```php
<?php

use App\Service\MyService;
use Selective\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class MyServiceProviderFactory
{
    /**
     * @return array<string, callable>
     */
    public function __invoke(): array
    {
        return [
            MyService::class => function (ContainerInterface $container) {
                return new MyService($container->get(LoggerInterface::class));
            },
        ];
    }
}

$container = new Container();
$container->factories((new MyServiceProviderFactory())());
```

## IDE integration

If you use PhpStorm, then create a new file `.phpstorm.meta.php`
in your project root directory and copy/paste the following content:

```php
<?php

namespace PHPSTORM_META;

override(\Psr\Container\ContainerInterface::get(0), map(['' => '@']));
```

## Credits

* Dominik Zogg (chubbyphp)

## Similar libraries

* https://github.com/chubbyphp/chubbyphp-container
* http://php-di.org/
* https://container.thephpleague.com/

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
