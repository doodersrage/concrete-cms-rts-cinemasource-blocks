<?php

namespace RtsCinemaSource;

use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;

class RouteList implements RouteListInterface
{
    public function loadRoutes(Router $router)
    {
        $router->post(
            '/api/rts_cinema_source/proxy',
            'RtsCinemaSource\Api\Controller\ProxyController::proxy'
        );
        $router->post(
            '/api/rts_cinema_source/session',
            'RtsCinemaSource\Api\Controller\SessionController::handle'
        );
        $router->get(
            '/api/rts_cinema_source/redirect',
            'RtsCinemaSource\Api\Controller\RedirectController::redirect'
        );
        $router->post(
            '/api/rts_cinema_source/complete',
            'RtsCinemaSource\Api\Controller\CompleteController::complete'
        );
        $router->get(
            '/api/rts_cinema_source/barcode',
            'RtsCinemaSource\Api\Controller\BarcodeController::render'
        );
    }
}
