![Laravel API to Postman Header](/header.png)

[![Latest Stable Version](https://poser.pugx.org/andreaselia/laravel-api-to-postman/v)](//packagist.org/packages/andreaselia/laravel-api-to-postman)
[![StyleCI](https://github.styleci.io/repos/323709695/shield?branch=master)](https://github.styleci.io/repos/323709695?branch=master)

# Laravel API to Postman

This package allows you to automatically generate a Postman collection based on your API routes.

## Postman Schema

The generator works for the latest version of the Postman Schema at the time of publication (v2.1.0).

## Installation

Install the package:

```bash
composer require andreaselia/laravel-api-to-postman
```

With auto-discovery you don't need to do anything else.

## Usage

To use the command simply run:

```bash
php artisan export:postman
```

- `--structured` generates routes in folders based on their namespace
- `--bearer=<token>` generates a token variable in Postman for the specified token
- `--base-url=<base_url>` defaults to https://api.example.com/ unless specified

## Contributing

You're more than welcome to submit a pull request, or if you're not feeling up to it - create an issue so someone else can pick it up.
