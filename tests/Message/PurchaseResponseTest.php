<?php

namespace Omnipay\BPOINT\Message;

use Omnipay\Tests\TestCase;

class PurchaseResponseTest extends TestCase
{
    /** @var PurchaseResponse */
    private $response;

    /**
     * Set up for the tests in this class
     */
    public function setUp()
    {
        $this->response = new PurchaseResponse($this->getMockRequest(), array(
            'APIResponse' => array(
                'ResponseCode' => 0,
                'ResponseText' => 'Success'
            ),
            'AuthKey' => 'df998fea-f309-4e6e-9629-7149799dc028'
        ));
    }

    public function testPurchaseSuccess()
    {
        $this->getMockRequest()->shouldReceive('getMerchantShortName')->once()->andReturn('DEMO123');

        $this->assertFalse($this->response->isSuccessful());
        $this->assertTrue($this->response->isRedirect());
        $this->assertSame('https://www.bpoint.com.au/pay/DEMO123', $this->response->getRedirectUrl());
        $this->assertSame('GET', $this->response->getRedirectMethod());
        $this->assertSame(
            array('in_pay_token' => 'df998fea-f309-4e6e-9629-7149799dc028'),
            $this->response->getRedirectData()
        );
        $this->assertNull($this->response->getTransactionReference());
        $this->assertSame('Success', $this->response->getMessage());
    }
}
