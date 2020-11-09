<?php

namespace Omnipay\BPOINT;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\ServerRequest;
use Omnipay\Common\Message\NotificationInterface;
use ReflectionObject;
use Omnipay\Tests\GatewayTestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

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

    public function testAcceptNotificationSuccess()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationSuccess.txt');
        $gateway = new RedirectGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('372626', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_COMPLETED, $notification->getTransactionStatus());
        $this->assertSame('Approved', $notification->getMessage());

        // ResponseInterface methods
        $response = $notification->send();
        $this->assertSame($notification, $response);
        $this->assertSame($notification, $response->getRequest());
        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isCancelled());
        $this->assertSame('0', $response->getCode());

        $this->assertSame('MC', $notification->getCardType());
    }

    public function testAcceptNotificationFailure()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationFailure.txt');
        $gateway = new RedirectGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('372627', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
        $this->assertSame('Transaction Declined', $notification->getMessage());

        // ResponseInterface methods
        $response = $notification->send();
        $this->assertFalse($response->isSuccessful());
    }

    public function testAcceptNotificationPending()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationPending.txt');
        $gateway = new RedirectGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertSame('372628', $notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_PENDING, $notification->getTransactionStatus());
        $this->assertSame('Transaction is Pending', $notification->getMessage());
    }

    public function testAcceptNotificationError()
    {
        $httpRequest = $this->setMockHttpRequest('AcceptNotificationError.txt');
        $gateway = new RedirectGateway($this->getHttpClient(), $httpRequest);
        $notification = $gateway->acceptNotification();

        // NotificationInterface methods
        $this->assertNull($notification->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $notification->getTransactionStatus());
        $this->assertNull($notification->getMessage());

        // bonus malformed JSON test
        $this->assertTrue(json_last_error() == JSON_ERROR_SYNTAX);
    }

    /**
     * Parses a saved raw request file into a new HTTP request object
     *
     * Initial file parsing adapted from TestCase::getMockHttpResponse()
     *
     * @param string $path  The request file
     *
     * @return HttpRequest  The new request
     */
    protected function setMockHttpRequest($path)
    {
        $ref = new ReflectionObject($this);
        $dir = dirname($ref->getFileName());
        // if mock file doesn't exist, check parent directory
        if (file_exists($dir.'/Mock/'.$path)) {
            $raw = file_get_contents($dir.'/Mock/'.$path);
        } elseif (file_exists($dir.'/../Mock/'.$path)) {
            $raw = file_get_contents($dir.'/../Mock/'.$path);
        } else {
            throw new Exception("Cannot open '{$path}'");
        }

        $guzzleRequest = Message::parseRequest($raw);
        // PSR-bridge requires a ServerRequestInterface
        $guzzleServerRequest = new ServerRequest(
            $guzzleRequest->getMethod(),
            $guzzleRequest->getUri(),
            $guzzleRequest->getHeaders(),
            $guzzleRequest->getBody(),
            $guzzleRequest->getProtocolVersion(),
            $_SERVER
        );

        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createRequest($guzzleServerRequest);
    }
}
