<?php

namespace RtsCinemaSource\Api\Controller;

use Concrete\Core\Controller\AbstractController;
use RtsCinemaSource\Service\IntegrationConfig;
use RtsCinemaSource\Service\RtsClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CompleteController extends AbstractController
{
    private const SESSION_KEY = 'rts_cinema_source.checkout';

    public function complete()
    {
        $session = $this->app->make('session');
        $checkout = $session->get(self::SESSION_KEY);

        if (!is_array($checkout)) {
            return new Response('No session data found', 400);
        }

        $checkout['paymentRes'] = [
            'PaymentID' => $this->request->request->get('PaymentID'),
            'ReturnCode' => $this->request->request->get('ReturnCode'),
            'ReturnMessage' => $this->request->request->get('ReturnMessage'),
        ];

        $configService = $this->app->make(IntegrationConfig::class);
        $config = $configService->getRtsFlatConfig();
        $convFee = (float) ($config['conv_fee'] ?? 1.35);
        $ticketSum = 0;

        $ticketsXml = '';
        foreach ($checkout['selTicketsQty'] as $ticket) {
            $ticketsXml .= '<Ticket><Amount>' . (int) $ticket['qty'] . '</Amount><TypeCode>' . $this->escapeXml((string) $ticket['code']) . '</TypeCode></Ticket>';
            $ticketSum += (int) $ticket['qty'];
        }

        $transactionId = $checkout['hostCheckout']['Packet']['CreatePayment']['TransactionId'] ?? '';
        $paymentId = $checkout['paymentRes']['PaymentID'];
        $returnCode = $checkout['paymentRes']['ReturnCode'];
        $returnMessage = urlencode((string) $checkout['paymentRes']['ReturnMessage']);
        $chargeAmount = number_format((float) ($checkout['orderSum'] ?? 0), 2, '.', '');
        $ticketFee = number_format($ticketSum * $convFee, 2, '.', '');

        $packet = '<?xml version="1.0"?><Request><Version>1</Version><Command>Buy</Command><Data><Packet><PurchaseTitles><PurchaseTitle><PerformanceID>' .
            $this->escapeXml((string) ($checkout['performanceId'] ?? '')) .
            '</PerformanceID><Tickets>' . $ticketsXml . '</Tickets></PurchaseTitle></PurchaseTitles><Fees><TicketFee>' . $ticketFee .
            '</TicketFee><TransactionFee>0</TransactionFee><Adjust>0</Adjust></Fees><Payments><Payment><Type>CreditCard</Type><TransactionId>' .
            $this->escapeXml((string) $transactionId) .
            '</TransactionId><ProcessCompletePostData>PaymentID=' . $paymentId . '&amp;ReturnCode=' . $returnCode . '&amp;ReturnMessage=' . $returnMessage .
            '</ProcessCompletePostData><ChargeAmount>' . $chargeAmount .
            '</ChargeAmount></Payment></Payments><CustomerInfo><EmailAddress>' .
            $this->escapeXml((string) ($checkout['email'] ?? '')) .
            '</EmailAddress></CustomerInfo></Packet></Data></Request>';

        $client = $this->app->make(RtsClient::class);
        $xml = $client->postXml($config, $packet);
        $checkout['rtsResult'] = $client->xmlToArray($xml);

        if (($checkout['rtsResult']['Packet']['Response']['ResponseText'] ?? '') === 'OK') {
            $this->sendConfirmationEmail($checkout, $configService);
        }

        $session->set(self::SESSION_KEY, $checkout);
        $session->save();

        $returnUrl = $configService->getAll()['site']['return_url'];

        return new RedirectResponse($returnUrl, 303);
    }

    protected function sendConfirmationEmail(array $checkout, IntegrationConfig $configService): void
    {
        $to = $checkout['email'] ?? '';
        if ($to === '') {
            return;
        }

        $subject = ($checkout['movieData']['title'] ?? 'Movie') . ' ' . ($checkout['selTime'] ?? '') . ' Ticket Purchase';
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $barcodeUrl = (string) \Concrete\Core\Support\Facade\Url::to('/api/rts_cinema_source/barcode');

        $message = '<html><body>';
        $message .= '<p>Please print or have your email available on mobile device upon arrival.</p>';
        $message .= '<h1>' . h($checkout['movieData']['title'] ?? '') . '</h1>';

        if (!empty($checkout['movieData']['photos']['photo'])) {
            $message .= '<p><img src="' . h($checkout['movieData']['photos']['photo']) . '"></p>';
        }

        $message .= '<p>Date/Time: ' . h($checkout['selTime'] ?? '') . '<br>';
        $message .= 'Total: $' . number_format((float) ($checkout['orderSum'] ?? 0), 2) . '</p>';
        $message .= '<p>Present this barcode on arrival:</p>';

        $barcodes = $checkout['rtsResult']['Packet']['Response']['Pickups']['Pickup']['BarCodes']['BarCode'] ?? [];
        if (isset($barcodes['CodeType'])) {
            $barcodes = [$barcodes];
        }

        foreach ($barcodes as $barcode) {
            if (($barcode['CodeType'] ?? '') === 'UPC') {
                $code = urlencode((string) ($barcode['BarCodeData'] ?? ''));
                $message .= '<p><img alt="ticket barcode" src="' . h($barcodeUrl . '?text=' . $code . '&size=40') . '" /></p>';
                $message .= '<p>Confirmation Number: ' . h($barcode['BarCodeData'] ?? '') . '</p>';
            }
        }

        $message .= '</body></html>';
        mail($to, $subject, $message, $headers);
    }

    protected function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
