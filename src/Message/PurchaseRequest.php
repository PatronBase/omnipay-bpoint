<?php

namespace Omnipay\BPOINT\Message;

use Omnipay\Common\Message\AbstractRequest;

/**
 * BPOINT Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    /** @var string */
    protected $endpoint = 'https://www.bpoint.com.au/webapi/v3/txns/';

    /** @var string */
    protected $action = 'processtxnauthkey';

    public function getUsername()
    {
        return $this->getParameter('username');
    }

    public function setUsername($value)
    {
        return $this->setParameter('username', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    public function getMerchantNumber()
    {
        return $this->getParameter('merchantNumber');
    }

    public function setMerchantNumber($value)
    {
        return $this->setParameter('merchantNumber', $value);
    }

    public function getMerchantShortName()
    {
        return $this->getParameter('merchantShortName');
    }

    public function setMerchantShortName($value)
    {
        return $this->setParameter('merchantShortName', $value);
    }

    public function getCustomerReferenceNumber1()
    {
        return $this->getParameter('customerReferenceNumber1');
    }

    /**
     * Set customer configurable reference #1
     *
     * @param bool $value  String, max 50 characters
     */
    public function setCustomerReferenceNumber1($value)
    {
        return $this->setParameter('customerReferenceNumber1', $value);
    }

    public function getCustomerReferenceNumber2()
    {
        return $this->getParameter('customerReferenceNumber2');
    }

    /**
     * Set customer configurable reference #2
     *
     * @param bool $value  String, max 50 characters
     */
    public function setCustomerReferenceNumber2($value)
    {
        return $this->setParameter('customerReferenceNumber2', $value);
    }

    public function getCustomerReferenceNumber3()
    {
        return $this->getParameter('customerReferenceNumber3');
    }

    /**
     * Set customer configurable reference #3
     *
     * @param bool $value  String, max 50 characters
     */
    public function setCustomerReferenceNumber3($value)
    {
        return $this->setParameter('customerReferenceNumber3', $value);
    }

    public function getGenerateToken()
    {
        return $this->getParameter('generateToken');
    }

    /**
     * Indicate whether or not to generate a token for the card used in the transaction
     *
     * @param bool $value  Generate a token or not
     */
    public function setGenerateToken($value)
    {
        return $this->setParameter('generateToken', $value);
    }

    public function getCustomerNumber()
    {
        return $this->getParameter('customerNumber');
    }

    /**
     * Set the unique customer ID in the merchant system
     *
     * @param bool $value  Customer number to set
     */
    public function setCustomerNumber($value)
    {
        return $this->setParameter('customerNumber', $value);
    }

    public function getData()
    {
        $this->validate('username', 'password', 'merchantNumber', 'merchantShortName', 'amount', 'currency');

        $amount = $this->getAmountInteger();
        $data = array(
            'ProcessTxnData' => array(
                'Action' => $amount > 0 ? 'payment' : 'verify_only',
                'TestMode' => $this->getTestMode(),
                'Amount' => $this->getAmountInteger(),
                'Crn1' => $this->getCustomerReferenceNumber1(),
                'Crn2' => $this->getCustomerReferenceNumber2(),
                'Crn3' => $this->getCustomerReferenceNumber3(),
                'Currency' => $this->getCurrency(),
                // 1 - no; 3 - always (don't leave it up to system or customer)
                'TokenisationMode' => $this->getGenerateToken() ? 3 : 1,
                'MerchantReference' => $this->getTransactionId(),
                'SubType' => 'single',
                'Type' => 'internet',
            ),
            'RedirectionUrl' => $this->getReturnUrl(),
            'WebHookUrl' => $this->getNotifyUrl(),
        );
        // add item details if available
        $items = $this->getItems();
        if ($items) {
            $data['Order'] = array('OrderItems' => array());
            foreach ($items as $item) {
                $data['Order']['OrderItems'][] = array(
                    'Comments' => '',
                    'Description' => $item->getDescription(),
                    'GiftMessage' => '',
                    'PartNumber' => '',
                    'ProductCode' => $item->getName(),
                    'Quantity' => $item->getQuantity(),
                    'SKU' => '',
                    'ShippingMethod' => '',
                    'ShippingNumber' => '',
                    // note: Item has no getPriceInteger; copied getCurrencyDecimalFactor() contents as it is private
                    'UnitPrice' => (int) round($item->getPrice() * pow(10, $this->getCurrencyDecimalPlaces())),
                );
            }
        }
        // add customer details if available
        $card = $this->getCard();
        if ($card) {
            $country = $card->getCountry();
            $data['Customer'] = array(
                'Address' => array(
                    'AddressLine1' => $card->getAddress1(),
                    'AddressLine2' => $card->getAddress2(),
                    'City' => $card->getCity(),
                    // should be ISO Alpha-3 country code
                    'CountryCode' => strlen($country) > 3 ? null : $country,
                    'PostCode' => $card->getPostcode(),
                    'State' => $card->getState(),
                ),
                'ContactDetails' => array(
                    'EmailAddress' => $card->getEmail(),
                ),
                'PersonalDetails' => array(
                    'FirstName' => $card->getFirstName(),
                    'LastName' => $card->getLastName(),
                ),
            );
            $data['EmailAddress'] = $card->getEmail();
            $customerNumber = $this->getCustomerNumber();
            if ($customerNumber) {
                $data['Customer']['CustomerNumber'] = $customerNumber;
            }
        }

        return $data;
    }

    public function sendData($data)
    {
        // submit data as request to Authkey endpoint
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getEndpoint(),
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->getAuthHeader(),
            ],
            json_encode($data)
        );
        // get response data
        $responseData = json_decode($httpResponse->getBody()->getContents(), true);

        return $this->response = new PurchaseResponse($this, $responseData);
    }

    public function getEndpoint()
    {
        return $this->endpoint.$this->action;
    }

    public function getAuthHeader()
    {
        return base64_encode($this->getUsername().'|'.$this->getMerchantNumber().':'.$this->getPassword());
    }
}
