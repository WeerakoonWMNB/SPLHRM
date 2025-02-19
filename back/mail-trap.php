<?php

use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\UnstructuredHeader;

require '../vendor/autoload.php';


$apiKey = '1521a1f7d4ab13e1727fea6326acc02e';
$mailtrap = MailtrapClient::initSendingEmails(
    apiKey: $apiKey,
);

$email = (new MailtrapEmail())
    ->from(new Address('hr_info@sadaharitha.com', 'Mailtrap Test'))
    ->to(new Address("nishshankaw@sadaharitha.com"))
    ->subject('You are awesome!')
    ->text('Congrats for sending test email with Mailtrap!')
    ->category('Integration Test')
;

$response = $mailtrap->send($email);

var_dump(ResponseHelper::toArray($response));
