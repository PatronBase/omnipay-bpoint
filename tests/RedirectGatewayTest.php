<?php

namespace Omnipay\BPOINT;

use Omnipay\Tests\GatewayTestCase;

class RedirectGatewayTest extends GatewayTestCase
{
    /** @var array */
    protected $options;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new RedirectGateway($this->getHttpClient(), $this->getHttpRequest());

        $this->options = array(
            'amount' => '1.45',
            'currency' => 'AUD',
            'username' => 'demo',
            'password' => 'DemoPassword!',
            'merchantNumber' => '5353109000000000',
            'merchantShortName' => 'DEMO123',
            'customerReferenceNumber1' => 'cr1',
            'customerReferenceNumber2' => 'cr2',
            'customerReferenceNumber3' => 'cr3',
            'generateToken' => true,
            'customerNumber' => 'cust456',
            'notifyUrl' => 'https://www.example.com/notify',
            'returnUrl' => 'https://www.example.com/return',
            'transactionId' => '123abc',
            'testMode' => true,
        );
    }

    public function testPurchase()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');
        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('https://www.bpoint.com.au/pay/DEMO123', $response->getRedirectUrl());
        $this->assertSame('Success', $response->getMessage());
    }

    public function testPurchaseError()
    {
        $this->setMockHttpResponse('PurchaseError.txt');
        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('https://www.bpoint.com.au/pay/DEMO123', $response->getRedirectUrl());
        $this->assertSame('Invalid credentials', $response->getMessage());
    }

    public function testCompletePurchaseSuccess()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'ResponseCode' => '0',
                'ResponseText' => 'Success',
                'ResultKey' => '13cfa799-8278-4872-a705-7ed49d516c0b',
            )
        );
        $this->setMockHttpResponse('CompletePurchaseSuccess.txt');
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('372626', $response->getTransactionReference());
        $this->assertSame('Approved', $response->getMessage());
        $this->assertSame('MC', $response->getCardType());
    }

    public function testCompletePurchaseFailure()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'ResponseCode' => '0',
                'ResponseText' => 'Success',
                'ResultKey' => '13cfa799-8278-4872-a705-7ed49d516c0b',
            )
        );

        $this->setMockHttpResponse('CompletePurchaseFailure.txt');
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('Invalid card number', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getCardType());
    }

    public function testCompletePurchaseError()
    {
        $this->getHttpRequest()->request->replace(
            array(
                'ResponseCode' => '1',
                'ResponseText' => 'Invalid credentials',
            )
        );

        $this->setMockHttpResponse('CompletePurchaseError.txt');
        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('Invalid credentials', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getCardType());
    }
}
