![Laravel Abilities banner](/art/banner-light.jpg#gh-light-mode-only)
![Laravel Abilities banner](/art/banner-dark.jpg#gh-dark-mode-only)

[![tests](https://github.com/craftzing/php-testbench/actions/workflows/tests.yml/badge.svg)](https://github.com/craftzing/php-testbench/actions/workflows/tests.yml)
[![static-analysis](https://github.com/craftzing/php-testbench/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/craftzing/php-testbench/actions/workflows/static-analysis.yml)
[![license](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat&color=4D6CB8)](https://github.com/craftzing/php-testbench/blob/master/LICENSE)

PHP Testbench is a set of common testing utilities we use at [Craftzing](https://craftzing.com) across all of our PHP 
projects.

# 🔥 Features

- Immutable object factories with an API similar to Laravel's [Eloquent Factories](https://laravel.com/docs/eloquent-factories).
- Custom [PHPUnit](https://phpunit.de) constraints.
- Extended PHPUnit constraints for Laravel.
- Common test doubles.

# 🏁 Getting started

> [!IMPORTANT]
> This package has not been released yet. However, as it merely contains testing utilities, it can safely be installed
> as a dev dependency. Make sure to add the following configuration to your composer.json:
> ```json
> "repositories": [
>     {
>         "type": "vcs",
>         "url": "https://github.com/craftzing/php-testbench.git"
>     }
> ],
> ```

This package requires:
- [PHP](https://www.php.net/supported-versions.php) 8.4

You can install this package using [Composer](https://getcomposer.org) by running the following command:
```shell
composer require craftzing/php-testbench --dev
```

# 📝 Changelog

Check out our [Change log](/CHANGELOG.md) for information on what has changed recently.

# 🤝 How to contribute

Have an idea for a feature? Wanna improve the docs? Found a bug? Check out our [Contributing guide](/CONTRIBUTING.md).

# 💙 Thanks to...

- [The entire Craftzing team](https://craftzing.com)
- [All current and future contributors](https://github.com/creaftzing/laravel-abilities/graphs/contributors)

# 👮 Security

If you discover any security-related issues, please email security@craftzing.com instead of using the issue tracker.

# 🔑 License

The MIT License (MIT). Please see [License File](/LICENSE) for more information.
