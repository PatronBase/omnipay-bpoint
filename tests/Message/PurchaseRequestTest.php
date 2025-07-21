<?php

namespace Omnipay\BPOINT\Message;

use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    /** @var PurchaseRequest */
    private $request;

    /** @var mixed[]  Data to initialize the request with */
    private $options;

    public function setUp(): void
    {
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
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
            'generateToken' => false,
            'customerNumber' => 'cust456',
            'notifyUrl' => 'https://www.example.com/notify',
            'returnUrl' => 'https://www.example.com/return',
            'transactionId' => '123abc',
            'testMode' => true,
        );
        $this->request->initialize($this->options);
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        $this->assertSame('payment', $data['ProcessTxnData']['Action']);
        $this->assertTrue($data['ProcessTxnData']['TestMode']);
        $this->assertSame(145, $data['ProcessTxnData']['Amount']);
        $this->assertSame('cr1', $data['ProcessTxnData']['Crn1']);
        $this->assertSame('cr2', $data['ProcessTxnData']['Crn2']);
        $this->assertSame('cr3', $data['ProcessTxnData']['Crn3']);
        $this->assertSame('AUD', $data['ProcessTxnData']['Currency']);
        $this->assertSame(1, $data['ProcessTxnData']['TokenisationMode']);
        $this->assertSame('123abc', $data['ProcessTxnData']['MerchantReference']);
        $this->assertSame('single', $data['ProcessTxnData']['SubType']);
        $this->assertSame('internet', $data['ProcessTxnData']['Type']);
        $this->assertSame('https://www.example.com/return', $data['RedirectionUrl']);
        $this->assertSame('https://www.example.com/notify', $data['WebHookUrl']);
    }

    public function testGetDataOnlyGetToken()
    {
        // override some data
        $this->options = array_merge($this->options, array('generateToken' => true, 'amount' => '0.00'));
        $this->request->initialize($this->options);
        $data = $this->request->getData();

        $this->assertSame('verify_only', $data['ProcessTxnData']['Action']);
        $this->assertSame(0, $data['ProcessTxnData']['Amount']);
        $this->assertSame(3, $data['ProcessTxnData']['TokenisationMode']);
    }

    public function testGetDataWithItems()
    {
        // override some data
        $this->options = array_merge(
            $this->options,
            array(
                'items' => array(
                    array('name' => 'Donation', 'description' => 'Fundraiser', 'quantity' => '1', 'price' => '1.00'),
                    array('name' => 'Fees', 'description' => 'Processing fees', 'quantity' => '3', 'price' => '0.15'),
                )
            )
        );
        $this->request->initialize($this->options);
        $data = $this->request->getData();

        $this->assertSame(2, count($data['Order']['OrderItems']));

        $this->assertSame('', $data['Order']['OrderItems'][0]['Comments']);
        $this->assertSame('Fundraiser', $data['Order']['OrderItems'][0]['Description']);
        $this->assertSame('', $data['Order']['OrderItems'][0]['GiftMessage']);
        $this->assertSame('', $data['Order']['OrderItems'][0]['PartNumber']);
        $this->assertSame('Donation', $data['Order']['OrderItems'][0]['ProductCode']);
        $this->assertSame('1', $data['Order']['OrderItems'][0]['Quantity']);
        $this->assertSame('', $data['Order']['OrderItems'][0]['SKU']);
        $this->assertSame('', $data['Order']['OrderItems'][0]['ShippingMethod']);
        $this->assertSame('', $data['Order']['OrderItems'][0]['ShippingNumber']);
        $this->assertSame(100, $data['Order']['OrderItems'][0]['UnitPrice']);

        $this->assertSame('', $data['Order']['OrderItems'][1]['Comments']);
        $this->assertSame('Processing fees', $data['Order']['OrderItems'][1]['Description']);
        $this->assertSame('', $data['Order']['OrderItems'][1]['GiftMessage']);
        $this->assertSame('', $data['Order']['OrderItems'][1]['PartNumber']);
        $this->assertSame('Fees', $data['Order']['OrderItems'][1]['ProductCode']);
        $this->assertSame('3', $data['Order']['OrderItems'][1]['Quantity']);
        $this->assertSame('', $data['Order']['OrderItems'][1]['SKU']);
        $this->assertSame('', $data['Order']['OrderItems'][1]['ShippingMethod']);
        $this->assertSame('', $data['Order']['OrderItems'][1]['ShippingNumber']);
        $this->assertSame(15, $data['Order']['OrderItems'][1]['UnitPrice']);
    }

    public function testGetDataWithCustomerDetails()
    {
        // override some data
        $this->options = array_merge(
            $this->options,
            array(
                'card' => array(
                    'firstName' => 'Tim',
                    'lastName'  => 'McTest',
                    'address1'  => '68 Arthur Street',
                    'address2'  => '',
                    'city'      => 'Warren',
                    'postcode'  => '2824',
                    'state'     => 'NSW',
                    'country'   => 'AUS',
                    'phone'     => '1234567890',
                    'email'     => 'customer@example.com',
                )
            )
        );
        $this->request->initialize($this->options);
        $data = $this->request->getData();

        $this->assertSame('customer@example.com', $data['EmailAddress']);

        $this->assertSame('68 Arthur Street', $data['Customer']['Address']['AddressLine1']);
        $this->assertSame('', $data['Customer']['Address']['AddressLine2']);
        $this->assertSame('Warren', $data['Customer']['Address']['City']);
        $this->assertSame('AUS', $data['Customer']['Address']['CountryCode']);
        $this->assertSame('2824', $data['Customer']['Address']['PostCode']);
        $this->assertSame('NSW', $data['Customer']['Address']['State']);

        $this->assertSame('customer@example.com', $data['Customer']['ContactDetails']['EmailAddress']);

        $this->assertSame('Tim', $data['Customer']['PersonalDetails']['FirstName']);
        $this->assertSame('McTest', $data['Customer']['PersonalDetails']['LastName']);

        $this->assertSame('cust456', $data['Customer']['CustomerNumber']);
    }

    public function testGetDataWithCustomerDetailsInvalidCountryCode()
    {
        // override some data
        $this->options = array_merge(
            $this->options,
            array('card' => array('country' => 'Australia'))
        );
        $this->request->initialize($this->options);
        $data = $this->request->getData();

        $this->assertNull($data['Customer']['Address']['CountryCode']);
    }

    public function testGetAuthHeader()
    {
        $this->assertSame('ZGVtb3w1MzUzMTA5MDAwMDAwMDAwOkRlbW9QYXNzd29yZCE=', $this->request->getAuthHeader());
    }
}
