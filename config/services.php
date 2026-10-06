<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'product_images' => [
        'base_url' => env('PRODUCT_IMAGES_BASE_URL', 'http://192.168.2.7:8099'),
    ],

    'erp' => [
        'base_url' => env('ERP_API_BASE_URL', 'http://192.168.2.7:8080/api'),
        'api_key' => env('ERP_API_KEY', 'f745d8e577bbf01a7b0c0e6ea24e5e2ccb742a3a21953786a81a2a2f66fd850d'),
        'default_sucursal' => (int) env('ERP_DEFAULT_SUCURSAL', 2),
        'default_forma_pago' => (int) env('ERP_DEFAULT_FORMA_PAGO', 2),
        'default_bodega_detalle' => (int) env('ERP_DEFAULT_BODEGA_DETALLE', 218),
    ],

];
