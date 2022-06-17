<?php

use PayPalCheckoutSdk\Webhook\WebhookRequest;
use PayPalCheckoutSdk\Client\Client;
use API\VerifyWebhookSignature;

include __DIR__.'/../bootstrap.php';

$webhookId=$paypalConfig['webhook_id'];
if(!$webhookId && empty($webhookId)){
    throw new Exception('The Veirification is missing the WEBHOOK ID ');
}
$WebhookDir=__DIR__.'/../temp/WebhookEvents/';
if( !is_dir( $WebhookDir ) )
    mkdir( $WebhookDir, 0777, true );

$postFileName=date('Ymd-His');
$bodyReceivedJson = file_get_contents('php://input');
$bodyReceivedEncode=json_encode($bodyReceivedJson);
$postFileName=$bodyReceivedEncode->resource?$bodyReceivedEncode->resource->id:$postFileName;

try {
    
    file_put_contents($WebhookDir.'event-response-'.$postFileName.'.json',$bodyReceivedJson);

    $headers = getallheaders();
    $headers = array_change_key_case($headers, CASE_UPPER);
    session_start();
    $signatureVerification = new VerifyWebhookSignature();
    $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
    $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
    $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
    $signatureVerification->setWebhookId($webhookId);
    $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
    $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
    
    $signatureVerification->setRequestBody($bodyReceivedJson);
    $requestBody=$signatureVerification->toJSON();
    session_abort();
    file_put_contents($WebhookDir.'make-request-body-'.$postFileName.'.json',$requestBody);
} catch (\Throwable $th) {
    file_put_contents($WebhookDir.'make-request-error-'.$postFileName.'.log',$th);
    exit(1);
}

try {
$request = new WebhookRequest();
$request->body =$requestBody;
$client = Client::make($paypalConfig['client_id'],$paypalConfig['client_secret'],$app_mode);
$response = $client->execute($request);
$data=json_encode($response);
    file_put_contents($WebhookDir.'verification-status-'.$postFileName.'.json',$data);
} catch (Exception $e) {
    file_put_contents($WebhookDir.'verification-error-'.$postFileName.'.log',$e);
    exit(1);
}
exit(1);
