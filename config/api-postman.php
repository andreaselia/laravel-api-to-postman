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

];
