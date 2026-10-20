![Laravel API to Postman Header](/header.png)

[![Latest Stable Version](https://poser.pugx.org/andreaselia/laravel-api-to-postman/v)](//packagist.org/packages/andreaselia/laravel-api-to-postman)

# Laravel API to Postman

This package allows you to automatically generate a Postman collection based on your API routes. It also provides basic configuration and support for bearer auth tokens and basic auth for routes behind an auth middleware.

For ```POST``` and ```PUT``` requests that utilizes a FormRequest, you can optionally scaffold the request, and publish rules in raw or human readable format.
## Postman Schema

The generator works for the latest version of the Postman Schema at the time of publication (v2.1.0).

## Installation

Install the package:

```bash
composer require andreaselia/laravel-api-to-postman
```

Publish the config file:

```bash
php artisan vendor:publish --provider="AndreasElia\PostmanGenerator\PostmanGeneratorServiceProvider"
```

## Configuration

You can modify any of the `api-postman.php` config values to suit your export requirements.

Click [here](/config/api-postman.php) to view the config attributes.

## Usage

The collection is saved in the `postman` directory of the disk configured by the `disk` option (the `local` disk by default, which is `storage/app/private` on Laravel 11+ and `storage/app` on older versions). The full path is printed once the export has finished.

To use the command simply run:

```bash
php artisan export:postman
```

The following usage will generate routes with the bearer token specified.

```bash
php artisan export:postman --bearer="1|XXNKXXqJjfzG8XXSvXX1Q4pxxnkXmp8tT8TXXKXX"
```

The following usage will generate routes with the basic auth specified.

```bash
php artisan export:postman --basic="username:password123"
```

If both auths are specified, bearer will be favored.

The auth header is only added to routes that use the middleware configured by the `auth_middleware` option (`auth:api` by default), or any `auth` / `auth:*` middleware such as `auth:sanctum`.

Instead of passing an option, you can also configure the authentication in your `.env` file. Options passed to the command take precedence over these values.

```dotenv
POSTMAN_EXPORT_AUTH_METHOD=bearer # or basic
POSTMAN_EXPORT_AUTH_TOKEN=your-token
```

If a method is set without a token, the requests will use the `{{token}}` collection variable, which you can fill in from within Postman.

## Examples

This is with the default configuration and a bearer token passed in:

```bash
php artisan export:postman --bearer=123456789
```

- [Example routes](/examples/api.php)
- [Example default output](/examples/2021_02_04_151948_postman.json)
- [Example structured output](/examples/2021_02_04_155327_postman.json)

## Contributing

You're more than welcome to submit a pull request, or if you're not feeling up to it - create an issue so someone else can pick it up.
