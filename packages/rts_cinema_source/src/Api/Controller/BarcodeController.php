<?php

namespace RtsCinemaSource\Api\Controller;

use Concrete\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class BarcodeController extends AbstractController
{
    public function render()
    {
        $text = (string) $this->request->query->get('text', '0');
        $size = (string) $this->request->query->get('size', '20');
        $codeType = (string) $this->request->query->get('codetype', 'code128');

        require_once __DIR__ . '/../../Service/BarcodeGenerator.php';

        ob_start();
        barcode('', $text, $size, 'horizontal', $codeType, false);
        $png = ob_get_clean();

        return new Response($png, 200, ['Content-Type' => 'image/png']);
    }
}
