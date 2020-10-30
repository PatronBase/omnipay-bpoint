<?php

namespace Omnipay\BPOINT\Message;

use Omnipay\Tests\TestCase;

class CompletePurchaseResponseTest extends TestCase
{
    /** @var CompletePurchaseResponse */
    private $response;

    /** @var mixed[]  Parsed TxnResp data from a transaction result */
    private $responseData;

    public function setUp()
    {
        // demo data from the BPOINT API documentation
        $this->responseData = array(
            'Action' => 'payment',
            'Amount' => 19900,
            'AmountOriginal' => 19800,
            'AmountSurcharge' => 100,
            'ThreeDSResponse' => null,
            'AuthoriseId' => '372626',
            'BankAccountDetails' => null,
            'BankResponseCode' => '00',
            'CVNResult' => array(
                'CVNResultCode' => 'Unsupported'
            ),
            'CardDetails' => array(
                'CardHolderName' => 'John Smith',
                'ExpiryDate' => '0521',
                'MaskedCardNumber' => '512345...346',
                'Category' => 'STANDARD',
                'Issuer' => 'BANCO DEL PICHINCHA, C.A.',
                'IssuerCountryCode' => 'ECU',
                'Localisation' => 'international',
                'SubType' => 'credit'
            ),
            'CardType' => 'MC',
            'Currency' => 'AUD',
            'MerchantReference' => 'test merchant ref',
            'IsThreeDS' => false,
            'IsCVNPresent' => true,
            'MerchantNumber  ' => '5353109000000000',
            'OriginalTxnNumber' => null,
            'ProcessedDateTime' => '2014-12-12T12:15:19.6370000',
            'RRN' => '434612372626',
            'ReceiptNumber' => '49316411177',
            'Crn1' => 'test crn1',
            'Crn2' => 'test crn2',
            'Crn3' => 'test crn3',
            'ResponseCode' => '0',
            'ResponseText' => 'Approved',
            'BillerCode' => null,
            'SettlementDate' => '20141212',
            'Source' => 'api',
            'StoreCard' => false,
            'IsTestTxn' => false,
            'SubType' => 'single',
            'TxnNumber' => '1177',
            'DVToken' => null,
            'Type' => 'internet',
            'FraudScreeningResponse' => array(
                'ReDResponse' => array(
                    'FRAUD_REC_ID' => '123412341234SAX20150101100000000',
                    'FRAUD_RSP_CD' => '0100',
                    'FRAUD_STAT_CD' => 'ACCEPT',
                    'ORD_ID' => '12341234',
                    'REQ_ID' => '123412341234',
                    'STAT_CD' => 'PENDING'
                ),
                'ResponseCode' => '',
                'ResponseMessage' => '',
                'TxnRejected' => false
            ),
            'StatementDescriptor' => array(
                'AddressLine1' => '123 Drive Street',
                'AddressLine2' => '',
                'City' => 'Melbourne',
                'CompanyName' => 'A Company Name',
                'CountryCode' => 'AUS',
                'Postcode' => '3000',
                'State' => 'Victoria',
                'MerchantName' => 'A Merchant Name',
                'PhoneNumber' => '0123456789'
            )
        );
    }

    public function testCompletePurchaseSuccess()
    {
        $this->getMockRequest()->shouldReceive('getData')->once()
            ->andReturn(array(
                'ResponseCode' => 0,
                'ResponseText' => 'Success',
                'ResultKey' => '13cfa799-8278-4872-a705-7ed49d516c0b'
            ));

        $this->response = new CompletePurchaseResponse($this->getMockRequest(), $this->responseData);

        $this->assertTrue($this->response->isSuccessful());
        $this->assertFalse($this->response->isRedirect());
        $this->assertSame('Approved', $this->response->getMessage());
        $this->assertSame('372626', $this->response->getTransactionReference());
        $this->assertSame('MC', $this->response->getCardType());

        // confirm the request format was valid
        $requestData = $this->response->getRequest()->getData();
        $this->assertSame(0, $requestData['ResponseCode']);
        $this->assertSame('Success', $requestData['ResponseText']);

        $data = $this->response->getData();
        $this->assertSame('0', $data['ResponseCode']);
        $this->assertSame('00', $data['BankResponseCode']);
    }

    public function testCompletePurchaseFailure()
    {
        $this->getMockRequest()->shouldReceive('getData')->once()
            ->andReturn(array(
                'ResponseCode' => 0,
                'ResponseText' => 'Success',
                'ResultKey' => '13cfa799-8278-4872-a705-7ed49d516c0b'
            ));

        // adjust to fail
        $this->responseData['AuthoriseId'] = null;
        $this->responseData['BankResponseCode'] = '14';
        $this->responseData['ResponseCode'] = '2';
        $this->responseData['ResponseText'] = 'Invalid card number';
        unset($this->responseData['CardDetails'], $this->responseData['CardType']);

        $this->response = new CompletePurchaseResponse($this->getMockRequest(), $this->responseData);

        $this->assertFalse($this->response->isSuccessful());
        $this->assertFalse($this->response->isRedirect());
        $this->assertSame('Invalid card number', $this->response->getMessage());
        $this->assertNull($this->response->getTransactionReference());
        $this->assertNull($this->response->getCardType());

        // confirm the request format was valid
        $requestData = $this->response->getRequest()->getData();
        $this->assertSame(0, $requestData['ResponseCode']);
        $this->assertSame('Success', $requestData['ResponseText']);

        $data = $this->response->getData();
        $this->assertSame('2', $data['ResponseCode']);
        $this->assertSame('14', $data['BankResponseCode']);
    }

    public function testCompletePurchaseError()
    {
        // TxnResp fields are ignored when API response is not successful
        $this->response = new CompletePurchaseResponse(
            $this->getMockRequest(),
            array(
                'ResponseCode' => 1,
                'ResponseText' => 'Invalid credentials'
            )
        );

        $this->assertFalse($this->response->isSuccessful());
        $this->assertFalse($this->response->isRedirect());
        $this->assertSame('Invalid credentials', $this->response->getMessage());
        $this->assertNull($this->response->getTransactionReference());
        $this->assertNull($this->response->getCardType());

        $data = $this->response->getData();

        $this->assertSame(1, $data['ResponseCode']);
    }
}
