<?php

namespace RtsCinemaSource\Api\Controller;

use Concrete\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SessionController extends AbstractController
{
    private const SESSION_KEY = 'rts_cinema_source.checkout';

    public function handle()
    {
        $session = $this->app->make('session');
        $method = $this->request->request->get('method');

        if ($method === 'set') {
            $session->set(self::SESSION_KEY, $this->request->request->get('data'));
            $session->save();

            return new JsonResponse(['status' => 'saved']);
        }

        if ($method === 'get') {
            $data = $session->get(self::SESSION_KEY);

            return new Response(
                is_string($data) ? $data : json_encode($data),
                200,
                ['Content-Type' => 'application/json']
            );
        }

        return new JsonResponse(['error' => 'Invalid method'], 400);
    }
}
