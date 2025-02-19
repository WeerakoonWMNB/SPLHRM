<?php

$curl = curl_init();

$data = [
    "from" => [
        "email" => "hello@sadaharitha.com",
        "name" => "Mailtrap Test"
    ],
    "to" => [
        ["email" => "sadaharithap@gmail.com"]
    ],
    "subject" => "You are awesome!",
    "text" => "Congrats for sending test email with Mailtrap!",
    "category" => "Integration Test"
];

curl_setopt_array($curl, [
    CURLOPT_URL => "https://send.api.mailtrap.io/api/send",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer 1521a1f7d4ab13e1727fea6326acc02e",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($curl);
$error = curl_error($curl);

curl_close($curl);

if ($error) {
    echo "cURL Error: " . $error;
} else {
    echo "Response: " . $response;
}

?>
