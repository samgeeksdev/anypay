<?php

namespace Samgeeksdev\Anypay\Drivers\Payfa;

use GuzzleHttp\Client;
use Samgeeksdev\Anypay\Abstracts\Driver;
use Samgeeksdev\Anypay\Contracts\ReceiptInterface;
use Samgeeksdev\Anypay\Exceptions\InvalidPaymentException;
use Samgeeksdev\Anypay\Exceptions\PurchaseFailedException;
use Samgeeksdev\Anypay\Invoice;
use Samgeeksdev\Anypay\Receipt;
use Samgeeksdev\Anypay\RedirectionForm;
use Samgeeksdev\Anypay\Request;

class Payfa extends Driver
{
    /**
     * Payfa Client.
     *
     * @var object
     */
    protected $client;

    /**
     * Invoice
     *
     * @var Invoice
     */
    protected $invoice;

    /**
     * Driver settings
     *
     * @var object
     */
    protected $settings;

    /**
     * Payfa constructor.
     * Construct the class with the relevant settings.
     *
     * @param Invoice $invoice
     * @param $settings
     */
    public function __construct(Invoice $invoice, $settings)
    {
        $this->invoice($invoice); // Set the invoice.
        $this->settings = (object)$settings; // Set settings.
        $this->client = new Client();
    }

    /**
     * Retrieve data from details using its name.
     *
     * @return string
     */
    private function extractDetails($name)
    {
        return empty($this->invoice->getDetails()[$name]) ? null : $this->invoice->getDetails()[$name];
    }

    public function purchase()
    {
        $mobile = $this->extractDetails('mobile');
        $cardNumber = $this->extractDetails('cardNumber');

        $data = array(
            'amount' => $this->invoice->getAmount() * ($this->settings->currency == 'T' ? 10 : 1), // convert to rial
            'callbackUrl' => $this->settings->callbackUrl,
            'mobileNumber' => $mobile,
            'invoiceId' => $this->invoice->getUuid(),
            'cardNumber' => $cardNumber
        );

        $response = $this->client->request(
            'POST',
            $this->settings->apiPurchaseUrl,
            [
                "json" => $data,
                "http_errors" => false,
                "headers" => [
                    "X-API-Key" => $this->settings->apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]
        );
        $body = json_decode($response->getBody()->getContents(), true);


        if ($response->getStatusCode() != 200) {
            throw new PurchaseFailedException($body["title"]);
        }

        $this->invoice->transactionId($body['paymentId']);

        // return the transaction's id
        return $this->invoice->getTransactionId();
    }

    /**
     * Pay the Invoice
     *
     * @return RedirectionForm
     */
    public function pay(): RedirectionForm
    {
        $payUrl = $this->settings->apiPaymentUrl . $this->invoice->getTransactionId();

        return $this->redirectWithForm($payUrl, [], 'GET');
    }


    /**
     * Verify payment
     *
     * @return ReceiptInterface
     *
     * @throws InvalidPaymentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verify(): ReceiptInterface
    {
        $paymentId = $this->invoice->getTransactionId() ?? Request::input('paymentId');


        $response = $this->client->request(
            'POST',
            $this->settings->apiVerificationUrl . $paymentId,
            [
                "http_errors" => false,
                "headers" => [
                    "X-API-Key" => $this->settings->apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]
        );
        $body = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() != 200) {
            $this->notVerified($body["message"], $response->getStatusCode());
        }

        return $this->createReceipt($body['transactionId']);
    }

    protected function createReceipt($referenceId)
    {
        return new Receipt('payfa', $referenceId);
    }

    /**
     * Trigger an exception
     *
     * @param $message
     * @throws InvalidPaymentException
     */
    private function notVerified($message, $status)
    {
        if (empty($message)) {
            throw new InvalidPaymentException('خطای ناشناخته ای رخ داده است.', (int)$status);
        } else {
            throw new InvalidPaymentException($message, (int)$status);
        }
    }
}
