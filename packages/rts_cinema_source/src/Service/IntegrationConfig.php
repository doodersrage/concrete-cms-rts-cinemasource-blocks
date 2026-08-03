<?php

namespace RtsCinemaSource\Service;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Package\Package;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;

class IntegrationConfig
{
    public function getAll(): array
    {
        $config = $this->getConfigRepository();

        return [
            'cinema_source' => [
                'base_url' => $config->get('cinema_source.base_url', 'https://webservice.cinema-source.com'),
                'api_version' => $config->get('cinema_source.api_version', '4.0'),
                'api_key' => $config->get('cinema_source.api_key', ''),
                'house_id' => $config->get('cinema_source.house_id', ''),
            ],
            'rts' => [
                'host' => $config->get('rts.host', '72352.formovietickets.com'),
                'port' => (int) $config->get('rts.port', 2235),
                'username' => $config->get('rts.username', ''),
                'password' => $config->get('rts.password', ''),
                'use_sandbox' => (bool) $config->get('rts.use_sandbox', false),
                'sandbox_host' => $config->get('rts.sandbox_host', '5.formovietickets.com'),
                'sandbox_username' => $config->get('rts.sandbox_username', 'test'),
                'sandbox_password' => $config->get('rts.sandbox_password', 'test'),
                'verify_ssl' => (bool) $config->get('rts.verify_ssl', false),
            ],
            'site' => [
                'process_complete_url' => $this->getProcessCompleteUrl($config),
                'return_url' => $this->getReturnUrl($config),
                'conv_fee' => (float) $config->get('site.conv_fee', 1.35),
            ],
        ];
    }

    public function getRtsFlatConfig(): array
    {
        $all = $this->getAll();

        return array_merge($all['rts'], [
            'process_complete_url' => $all['site']['process_complete_url'],
            'return_url' => $all['site']['return_url'],
            'conv_fee' => $all['site']['conv_fee'],
        ]);
    }

    public function getClientConfig(): array
    {
        $all = $this->getAll();

        return [
            'reqUrl' => (string) Url::to('/api/rts_cinema_source/proxy'),
            'sessUrl' => (string) Url::to('/api/rts_cinema_source/session'),
            'redirUrl' => (string) Url::to('/api/rts_cinema_source/redirect'),
            'processCompleteUrl' => $all['site']['process_complete_url'],
            'returnUrl' => $all['site']['return_url'],
            'convFee' => $all['site']['conv_fee'],
        ];
    }

    public function save(array $values): void
    {
        $config = $this->getConfigRepository();

        foreach ($values as $group => $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $key => $value) {
                $config->save($group . '.' . $key, $value);
            }
        }
    }

    protected function getConfigRepository(): Repository
    {
        $pkg = Package::getByHandle('rts_cinema_source');

        return $pkg->getFileConfig();
    }

    protected function getProcessCompleteUrl(Repository $config): string
    {
        $configured = trim((string) $config->get('site.process_complete_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return (string) Url::to('/api/rts_cinema_source/complete');
    }

    protected function getReturnUrl(Repository $config): string
    {
        $configured = trim((string) $config->get('site.return_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        $appUrl = Application::getApplicationURL();

        return rtrim((string) $appUrl, '/') . '/?paymentRes=1';
    }
}
