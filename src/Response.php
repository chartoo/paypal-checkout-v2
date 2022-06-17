<?php

use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Payments\CapturesGetRequest;
use PayPalCheckoutSdk\Client\Client;

include __DIR__.'/../bootstrap.php';

if (empty($_GET['token']) || empty($_GET['PayerID'])) {
    throw new Exception('The response is missing the Order ID');
}

$token = $_GET['token'];
$payerId = $_GET['PayerID'];

$request = new OrdersCaptureRequest($token);
$client = Client::make($paypalConfig['client_id'],$paypalConfig['client_secret'],$app_mode);
$response = $client->execute($request);

$data=json_encode($response);
try {
    $dir=$tempDir.'/OrdersCaptured/';
    if( !is_dir( $dir ) )
        mkdir( $dir, 0777, true );
    file_put_contents($dir.'order-captured-response-'.$token.'.json',$data);
} catch (Exception $e) {
    //throw $th;
}

header('location:'.$paypalConfig['success_url']);
exit(1);