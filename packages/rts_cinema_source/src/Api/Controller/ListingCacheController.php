<?php

namespace RtsCinemaSource\Api\Controller;

use Concrete\Core\Controller\AbstractController;
use RtsCinemaSource\Service\ListingBootstrap;
use Symfony\Component\HttpFoundation\Response;

class ListingCacheController extends AbstractController
{
    public function render()
    {
        $bootstrap = $this->app->make(ListingBootstrap::class);
        $error = $bootstrap->ensureListingCache();
        $content = $bootstrap->getListingCacheScript();

        if ($content === null) {
            $message = $error ?? t('Listing cache is unavailable.');

            return new Response('// ' . $message, 200, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
            ]);
        }

        return new Response($content, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
