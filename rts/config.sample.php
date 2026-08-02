<?php

/**
 * RTS POS proxy configuration.
 *
 * Copy to config.php and set credentials from your RTS account.
 * When running inside Concrete CMS, values from application/config/cinema_source.php are used instead.
 */
return [
    'host' => '72352.formovietickets.com',
    'port' => 2235,
    'username' => 'YOUR_RTS_USERNAME',
    'password' => 'YOUR_RTS_PASSWORD',
    'use_sandbox' => false,
    'sandbox_host' => '5.formovietickets.com',
    'sandbox_username' => 'test',
    'sandbox_password' => 'test',
    'verify_ssl' => false,
    'process_complete_url' => 'https://example.com/rts/procComp.php',
    'return_url' => 'https://example.com/showtimes',
    'conv_fee' => 1.35,
];
