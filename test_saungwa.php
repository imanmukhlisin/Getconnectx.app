<?php
$url = "https://app.saungwa.com/api/create-message";
$data = array(
  "appkey" => "782591d4-d65d-4bff-90f9-8fecd2a89e1d",
  "authkey" => "Xlii3w8Kw4k8wVx6ow57OG7BIfTIvDIFyNHAktP35ykBo6hwil",
  "to" => "6282223416945", // Ganti ke nomor kamu
  "message" => "Test auth via stream",
  "sandbox" => "false"
);

$options = array(
  'http' => array(
    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
    'method'  => 'POST',
    'content' => http_build_query($data),
    'ignore_errors' => true
  )
);

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

echo "RESPONSE:\n" . $response . "\n";
