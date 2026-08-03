<?php

namespace RtsCinemaSource\Api\Controller;

use Concrete\Core\Controller\AbstractController;
use RtsCinemaSource\Service\IntegrationConfig;
use RtsCinemaSource\Service\RtsClient;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProxyController extends AbstractController
{
    public function proxy()
    {
        $packet = $this->request->request->get('req');
        if (!$packet) {
            return new JsonResponse(['error' => 'Missing request payload'], 400);
        }

        $config = $this->app->make(IntegrationConfig::class);
        $client = $this->app->make(RtsClient::class);
        $xml = $client->postXml($config->getRtsFlatConfig(), (string) $packet);
        $parsed = simplexml_load_string($xml);

        if ($parsed === false) {
            return new JsonResponse(['error' => 'Invalid RTS response'], 502);
        }

        return new JsonResponse($client->xmlToArray($xml));
    }
}
