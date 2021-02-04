![Laravel API to Postman Header](/header.png)

[![Latest Stable Version](https://poser.pugx.org/andreaselia/laravel-api-to-postman/v)](//packagist.org/packages/andreaselia/laravel-api-to-postman)
[![StyleCI](https://github.styleci.io/repos/323709695/shield?branch=master)](https://github.styleci.io/repos/323709695?branch=master)

# Laravel API to Postman

This package allows you to automatically generate a Postman collection based on your API routes. It also provides basic configuration and support for bearer auth tokens for routes behind an auth middleware.

## Postman Schema

The generator works for the latest version of the Postman Schema at the time of publication (v2.1.0).

## Installation

Install the package:

```bash
composer require andreaselia/laravel-api-to-postman
```

Publish the config file:

```bash
php artisan vendor:publish --provider="AndreasElia\PostmanGenerator\PostmanGeneratorServiceProvider" --tag="config"
```

## Configuration

You can modify the `api-postman.php` config values:

- `structured` - If you want folders to be generated based on route names.
- `base_url` - The base URL for all of your endpoints.
- `auth_middleware` - The middleware which wraps your authenticated API routes.

## Usage

The output of the command being ran is your storage/app directory.

To use the command simply run:

```bash
php artisan export:postman
```

The following usage will generate routes with the bearer token specified.

```bash
php artisan export:postman --bearer=1|XXNKXXqJjfzG8XXSvXX1Q4pxxnkXmp8tT8TXXKXX
```

## Examples

This is with the default configuration and a bearer token passed in:

```bash
php artisan export:postman --bearer=123456789
```

Given the following `api.php` routes:

https://gist.github.com/AndreasElia/e4dbfc751e577add2c3eaaccaba5ad11

The output would be the following JSON which can be imported into Postman as a collection:

https://gist.github.com/AndreasElia/ffe8d7def3f108c452927e422d29fa9d

If you specify `structured` as `true` in the configuration file, the output will be:

https://gist.github.com/AndreasElia/7c7aee08dea40b6ff0b8ef30547ab721

## Contributing

You're more than welcome to submit a pull request, or if you're not feeling up to it - create an issue so someone else can pick it up.
