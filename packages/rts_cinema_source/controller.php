<?php

namespace Concrete\Package\RtsCinemaSource;

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;
use RtsCinemaSource\RouteList;

class Controller extends Package
{
    protected $pkgHandle = 'rts_cinema_source';

    protected $appVersionRequired = '9.0.0';

    protected $pkgVersion = '2.1.2';

    protected $pkgAutoloaderRegistries = [
        'src' => 'RtsCinemaSource',
    ];

    public function getPackageName()
    {
        return t('RTS Cinema Source');
    }

    public function getPackageDescription()
    {
        return t('Cinema Source showtime data with RTS POS online ticketing.');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installBlocks($pkg);
        SinglePage::add('/dashboard/rts_cinema_source', $pkg);

        return $pkg;
    }

    public function on_start()
    {
        $this->app->singleton(\RtsCinemaSource\Service\IntegrationConfig::class, function () {
            return new \RtsCinemaSource\Service\IntegrationConfig();
        });
        $this->app->singleton(\RtsCinemaSource\Service\RtsClient::class, function () {
            return new \RtsCinemaSource\Service\RtsClient();
        });

        $this->app->singleton(\RtsCinemaSource\Service\ListingBootstrap::class, function ($app) {
            return new \RtsCinemaSource\Service\ListingBootstrap($app);
        });

        $router = $this->app->make('router');
        (new RouteList())->loadRoutes($router);
    }

    protected function installBlocks($pkg)
    {
        foreach (
            [
                'movie_listing',
                'movie_listing_soon',
                'movie_gallery',
                'movie_gallery_soon',
            ] as $handle
        ) {
            BlockType::installBlockTypeFromPackage($handle, $pkg);
        }
    }
}
