# redis-cache

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Super simple redis caching implementation.

## Install

Via Composer

``` bash
$ composer require terah/redis-cache
```

## Usage

``` php

$redis          = new \Redis();
$redis->connect('127.0.0.1', 6379);

$namespace      = 'my-short-db-cache';
$defaultTtl     = 60 * 60; // 1 hour
$cache          = new Terah\RedisCache\RedisCache($redis, $defaultTtl, $namespace);

// Save your data
$cache->set('my-user-list', expensiveFunctionCall(), 60 * 60 * 2); // Ttl will default to $defaultTtl

// Fetch your data
$myData         = $cache->get('my-user-list');

// Deletes
$cache->delete('my-user-list');

// Convenient callback handler
$callback = function() {
    return expensiveDataFetch();
}
$data = $cache->remember('my-user-list', $callback, 60 * 60 * 1);

// Hierarchical keys - caching in 'directories'

$cache->set('/user_data/user_profiles/freddy', $data);
$cache->set('/user_data/user_profiles/betty', $data);
$cache->set('/user_data/user_profiles/micky', $data);

// now you can flush all user data:
$cache->delete('/user_data/');

```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email terry@terah.com.au instead of using the issue tracker.

## Credits

- [Terry Cullen][https://bitbucket.org/terahdigital]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/terah/redis-cache.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/terah/redis-cache/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/terah/redis-cache.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/terah/redis-cache.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/terah/redis-cache.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/terah/redis-cache
[link-travis]: https://travis-ci.org/terah/redis-cache
[link-scrutinizer]: https://scrutinizer-ci.com/g/terah/redis-cache/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/terah/redis-cache
[link-downloads]: https://packagist.org/packages/terah/redis-cache
[https://bitbucket.org/terahdigital]: https://bitbucket.org/terahdigital
[link-contributors]: ../../contributors
