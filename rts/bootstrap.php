<?php

/**
 * Load RTS configuration from config.php or Concrete CMS application config.
 */
function rts_load_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $defaults = require __DIR__ . '/config.sample.php';

    $localConfig = __DIR__ . '/config.php';
    if (file_exists($localConfig)) {
        $loaded = require $localConfig;
        if (is_array($loaded)) {
            $defaults = array_merge($defaults, $loaded);
        }
    }

    $appConfig = dirname(__DIR__) . '/application/config/cinema_source.php';
    if (file_exists($appConfig)) {
        $site = require $appConfig;
        if (is_array($site) && isset($site['rts']) && is_array($site['rts'])) {
            $defaults = array_merge($defaults, [
                'host' => $site['rts']['host'] ?? $defaults['host'],
                'port' => $site['rts']['port'] ?? $defaults['port'],
                'username' => $site['rts']['username'] ?? $defaults['username'],
                'password' => $site['rts']['password'] ?? $defaults['password'],
                'use_sandbox' => $site['rts']['use_sandbox'] ?? $defaults['use_sandbox'],
                'sandbox_host' => $site['rts']['sandbox_host'] ?? $defaults['sandbox_host'],
                'sandbox_username' => $site['rts']['sandbox_username'] ?? $defaults['sandbox_username'],
                'sandbox_password' => $site['rts']['sandbox_password'] ?? $defaults['sandbox_password'],
                'verify_ssl' => $site['rts']['verify_ssl'] ?? $defaults['verify_ssl'],
            ]);
        }
        if (is_array($site) && isset($site['site']) && is_array($site['site'])) {
            $defaults = array_merge($defaults, [
                'process_complete_url' => $site['site']['process_complete_url'] ?? $defaults['process_complete_url'],
                'return_url' => $site['site']['return_url'] ?? $defaults['return_url'],
                'conv_fee' => $site['site']['conv_fee'] ?? $defaults['conv_fee'],
            ]);
        }
    }

    $config = $defaults;

    return $config;
}

function rts_get_endpoint(array $config): array
{
    if (!empty($config['use_sandbox'])) {
        return [
            'host' => $config['sandbox_host'],
            'username' => $config['sandbox_username'],
            'password' => $config['sandbox_password'],
        ];
    }

    return [
        'host' => $config['host'],
        'username' => $config['username'],
        'password' => $config['password'],
    ];
}

function rts_post_xml(string $packet, array $config): string
{
    $endpoint = rts_get_endpoint($config);
    $port = (int) ($config['port'] ?? 2235);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://' . $endpoint['host'] . '/Data.ASP');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !empty($config['verify_ssl']));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, !empty($config['verify_ssl']) ? 2 : 0);
    curl_setopt($ch, CURLOPT_USERPWD, $endpoint['username'] . ':' . $endpoint['password']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $packet);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data !== false ? $data : '';
}
