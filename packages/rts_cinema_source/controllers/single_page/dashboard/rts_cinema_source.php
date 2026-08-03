<?php

namespace Concrete\Package\RtsCinemaSource\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Support\Facade\Url;
use RtsCinemaSource\Service\IntegrationConfig;

class RtsCinemaSource extends DashboardPageController
{
    public function view()
    {
        $config = $this->app->make(IntegrationConfig::class)->getAll();
        $this->set('cinemaSource', $config['cinema_source']);
        $this->set('rts', $config['rts']);
        $this->set('site', $config['site']);
        $this->set('apiEndpoints', [
            'proxy' => (string) Url::to('/api/rts_cinema_source/proxy'),
            'session' => (string) Url::to('/api/rts_cinema_source/session'),
            'redirect' => (string) Url::to('/api/rts_cinema_source/redirect'),
            'complete' => (string) Url::to('/api/rts_cinema_source/complete'),
            'barcode' => (string) Url::to('/api/rts_cinema_source/barcode'),
        ]);
    }

    public function save()
    {
        if (!$this->token->validate('save_rts_cinema_source')) {
            $this->error->add($this->token->getErrorMessage());

            return $this->view();
        }

        $post = $this->request->request;
        $config = $this->app->make(IntegrationConfig::class);

        $config->save([
            'cinema_source' => [
                'base_url' => trim((string) $post->get('cinema_source_base_url')),
                'api_version' => trim((string) $post->get('cinema_source_api_version')) ?: '4.0',
                'api_key' => trim((string) $post->get('cinema_source_api_key')),
                'house_id' => trim((string) $post->get('cinema_source_house_id')),
            ],
            'rts' => [
                'host' => trim((string) $post->get('rts_host')),
                'port' => (int) $post->get('rts_port', 2235),
                'username' => trim((string) $post->get('rts_username')),
                'password' => trim((string) $post->get('rts_password')),
                'use_sandbox' => (bool) $post->get('rts_use_sandbox'),
                'sandbox_host' => trim((string) $post->get('rts_sandbox_host')),
                'sandbox_username' => trim((string) $post->get('rts_sandbox_username')),
                'sandbox_password' => trim((string) $post->get('rts_sandbox_password')),
                'verify_ssl' => (bool) $post->get('rts_verify_ssl'),
            ],
            'site' => [
                'process_complete_url' => trim((string) $post->get('site_process_complete_url')),
                'return_url' => trim((string) $post->get('site_return_url')),
                'conv_fee' => (float) $post->get('site_conv_fee', 1.35),
            ],
        ]);

        $this->flash('success', t('RTS Cinema Source settings saved.'));
        $cache = $this->app->make('cache/expensive');
        $cache->deleteItem('movieFeed');

        return $this->buildRedirect($this->action(''));
    }
}
