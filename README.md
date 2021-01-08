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

You can also supply the `--structured` option to nest the routes into folders based on their name, or `--bearer` to create a bearer authorization token which can be managed in a single place within variables. For `--bearer` and `--base-url` you can specify a value or leave them empty by default.

## Contributing

You're more that welcome to submit a pull request, or if you're not feeling up to it - create an issue so someone else can pick it up.
