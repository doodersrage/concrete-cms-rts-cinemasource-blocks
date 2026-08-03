<?php

namespace RtsCinemaSource\Api\Controller;

use Concrete\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends AbstractController
{
    public function redirect()
    {
        $redirectUrl = $this->request->query->get('RedirectUrl');
        if (!$redirectUrl || !preg_match('#^https://#i', (string) $redirectUrl)) {
            return new Response('Invalid redirect URL', 400);
        }

        $fields = '';
        foreach ($this->request->query->all() as $key => $value) {
            if ($key === 'RedirectUrl') {
                continue;
            }
            $fields .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                h($key),
                h((string) $value)
            );
        }

        $html = sprintf(
            '<!DOCTYPE html><html><body><form action="%s" method="post" name="frm">%s</form><script>document.frm.submit();</script></body></html>',
            h((string) $redirectUrl),
            $fields
        );

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
