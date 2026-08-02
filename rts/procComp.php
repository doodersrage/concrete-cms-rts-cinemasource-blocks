<?php

session_start();

require __DIR__ . '/bootstrap.php';

if (empty($_SESSION['data']) || !is_string($_SESSION['data'])) {
    echo 'no session data found';
    exit;
}

$config = rts_load_config();
$sess_data = json_decode($_SESSION['data'], true);
if (!is_array($sess_data)) {
    echo 'no session data found';
    exit;
}

$sess_data['paymentRes'] = [
    'PaymentID' => $_POST['PaymentID'] ?? '',
    'ReturnCode' => $_POST['ReturnCode'] ?? '',
    'ReturnMessage' => $_POST['ReturnMessage'] ?? '',
];

$performanceID = $sess_data['performanceId'] ?? '';
$ticketSum = 0;
$convFee = (float) ($config['conv_fee'] ?? 1.35);

$package = '<Request>
                <Version>1</Version>
                <Command>Buy</Command>
                <Data>
                    <Packet>
                        <PurchaseTitles>
                            <PurchaseTitle>
                                <PerformanceID>' . htmlspecialchars((string) $performanceID, ENT_XML1) . '</PerformanceID>
                                <Tickets>';

foreach ($sess_data['selTicketsQty'] as $ticket) {
    $package .= '<Ticket>
                    <Amount>' . (int) $ticket['qty'] . '</Amount>
                    <TypeCode>' . htmlspecialchars((string) $ticket['code'], ENT_XML1) . '</TypeCode>
                </Ticket>';
    $ticketSum += (int) $ticket['qty'];
}

$transactionId = $sess_data['hostCheckout']['Packet']['CreatePayment']['TransactionId'] ?? '';
$paymentId = $sess_data['paymentRes']['PaymentID'];
$returnCode = $sess_data['paymentRes']['ReturnCode'];
$returnMessage = urlencode($sess_data['paymentRes']['ReturnMessage']);
$chargeAmount = number_format((float) ($sess_data['orderSum'] ?? 0), 2, '.', '');
$ticketFee = number_format($ticketSum * $convFee, 2, '.', '');
$email = htmlspecialchars((string) ($sess_data['email'] ?? ''), ENT_XML1);

$package .= '</Tickets>
                            </PurchaseTitle>
                        </PurchaseTitles>
                        <Fees>
                            <TicketFee>' . $ticketFee . '</TicketFee>
                            <TransactionFee>0</TransactionFee>
                            <Adjust>0</Adjust>
                        </Fees>
                        <Payments>
                            <Payment>
                                <Type>CreditCard</Type>
                                <TransactionId>' . htmlspecialchars((string) $transactionId, ENT_XML1) . '</TransactionId>
                                <ProcessCompletePostData>PaymentID=' . $paymentId . '&ReturnCode=' . $returnCode . '&ReturnMessage=' . $returnMessage . '</ProcessCompletePostData>
                                <ChargeAmount>' . $chargeAmount . '</ChargeAmount>
                            </Payment>
                        </Payments>
                        <CustomerInfo>
                            <EmailAddress>' . $email . '</EmailAddress>
                        </CustomerInfo>
                    </Packet>
                </Data>
            </Request>';

$data = rts_post_xml($package, $config);
$parsed = simplexml_load_string($data);
$sess_data['rtsResult'] = $parsed ? json_decode(json_encode($parsed), true) : [];

if (($sess_data['rtsResult']['Packet']['Response']['ResponseText'] ?? '') === 'OK') {
    $to = $sess_data['email'];
    $subject = ($sess_data['movieData']['title'] ?? 'Movie') . ' ' . ($sess_data['selTime'] ?? '') . ' Ticket Purchase';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $message = '<html><body>';
    $message .= '<p>Please print or have your email available on mobile device upon arrival.</p>';
    $message .= '<h1>' . htmlspecialchars((string) ($sess_data['movieData']['title'] ?? ''), ENT_QUOTES, 'UTF-8') . '</h1>';
    if (!empty($sess_data['movieData']['photos']['photo'])) {
        $message .= '<p><img src="' . htmlspecialchars((string) $sess_data['movieData']['photos']['photo'], ENT_QUOTES, 'UTF-8') . '"></p>';
    }
    $message .= '<p>Date/Time: ' . htmlspecialchars((string) ($sess_data['selTime'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>';
    $message .= 'Total: $' . number_format((float) ($sess_data['orderSum'] ?? 0), 2) . '</p>';
    $message .= '<p>Present this barcode on arrival:</p>';

    $barcodes = $sess_data['rtsResult']['Packet']['Response']['Pickups']['Pickup']['BarCodes']['BarCode'] ?? [];
    if (isset($barcodes['CodeType'])) {
        $barcodes = [$barcodes];
    }

    foreach ($barcodes as $barcode) {
        if (($barcode['CodeType'] ?? '') === 'UPC') {
            $barcodeData = urlencode((string) ($barcode['BarCodeData'] ?? ''));
            $message .= '<p><img alt="ticket barcode" src="/rts/barcode.php?text=' . $barcodeData . '&size=40" /></p>';
            $message .= '<p>Confirmation Number: ' . htmlspecialchars((string) ($barcode['BarCodeData'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    $message .= '</body></html>';

    mail($to, $subject, $message, $headers);
}

$_SESSION['data'] = json_encode($sess_data);

$returnUrl = $config['return_url'] ?: '/?paymentRes=1';
header('Location: ' . $returnUrl);
