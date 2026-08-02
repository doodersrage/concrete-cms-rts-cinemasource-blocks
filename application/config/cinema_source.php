<?php

/**
 * Site-wide Cinema Source and RTS POS configuration.
 *
 * Block-level settings in the West World Media block override these values when set.
 * Copy this file and fill in credentials assigned by Webedia / Cinema Source and RTS.
 */
return [
    'cinema_source' => [
        // Cinema Source webservice base URL (HTTPS required for current API)
        'base_url' => 'https://webservice.cinema-source.com',
        // API version assigned by Cinema Source (commonly 4.0 as of 2024+)
        'api_version' => '4.0',
        'api_key' => '',
        'house_id' => '',
    ],
    'rts' => [
        'host' => '72352.formovietickets.com',
        'port' => 2235,
        'username' => '',
        'password' => '',
        'use_sandbox' => false,
        'sandbox_host' => '5.formovietickets.com',
        'sandbox_username' => 'test',
        'sandbox_password' => 'test',
        'verify_ssl' => false,
    ],
    'site' => [
        // Full URLs used during RTS checkout (override for your domain)
        'process_complete_url' => '',
        'return_url' => '',
        'conv_fee' => 1.35,
    ],
];
