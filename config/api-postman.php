<?php

return [

    /*
     * Structured.
     *
     * If you want folders to be generated based on namespace.
     */

    'structured' => false,

    /*
     * Base URL.
     *
     * The base URL for all of your endpoints.
     */

    'base_url' => env('APP_URL', 'http://localhost'),

    /*
     * Auth Middleware.
     *
     * The middleware which wraps your authenticated API routes.
     *
     * E.g. auth:api, auth:sanctum
     */

    'auth_middleware' => 'auth:api',

    /*
     * Headers.
     *
     * The headers applied to all routes within the collection.
     */

    'headers' => [
        [
            'key' => 'Accept',
            'value' => 'application/json',
        ],
        [
            'key' => 'Content-Type',
            'value' => 'application/json',
        ],
    ],

    /*
     * Enable Form Data.
     *
     * Determines whether or not form data should be handled.
     */

    'enable_formdata' => false,

    /*
     * Form Data.
     *
     * The key/values to requests for form data dummy information.
     */

    'formdata' => [
        // 'email' => 'john@example.com',
        // 'password' => 'changeme',
    ],

];
