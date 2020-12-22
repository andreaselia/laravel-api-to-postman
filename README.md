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

The output Postman collection will have a `base_url` variable set by default for ease of use.

## Extra Usage

```bash
--structured # nest the routes into folders based on relationships
--bearer # create a bearer authorization token in variables and all routes that require it
```

## Contributing

You're more that welcome to submit a Pull Request, or if you're not feeling up to it - post an issue and someone else may pick it up.
