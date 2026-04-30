<?php
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://app.saungwa.com/api/create-message",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => array(
    "appkey" => "e46cb0ba-5a21-41df-a131-58dbbb940818",
    "authkey" => "RNXBbO8rh3lrIaOCBPRnSAuWbBHxMK17IYxQN2Y3slomEpPUCF",
    "to" => "6281234567890",
    "message" => "Test auth",
    "sandbox" => "false"
  ),
));
$response = curl_exec($curl);
curl_close($curl);
echo "RESPONSE:\n" . $response . "\n";
