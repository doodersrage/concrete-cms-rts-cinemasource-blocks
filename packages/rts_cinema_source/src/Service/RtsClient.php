<?php

namespace RtsCinemaSource\Service;

class RtsClient
{
    public function postXml(array $config, string $packet): string
    {
        $endpoint = $this->getEndpoint($config);
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

    public function xmlToArray(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $element = simplexml_load_string($xml);
        if ($element === false) {
            return [];
        }

        return json_decode(json_encode($element), true) ?: [];
    }

    protected function getEndpoint(array $config): array
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
}
