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

    public function getCreateToken()
    {
        return $this->getParameter('createToken');
    }

    public function setCreateToken($value)
    {
        return $this->setParameter('createToken', $value);
    }

    public function getMerchantShortName()
    {
        return $this->getParameter('merchantShortName');
    }

    public function setMerchantShortName($value)
    {
        return $this->setParameter('merchantShortName', $value);
    }

    public function getBillerCode()
    {
        return $this->getParameter('billerCode');
    }

    /**
     * Set biller code for the transaction (differentiate income streams); required for using stored card tokens
     *
     * @param string $value  String, max 50 characters
     */
    public function setBillerCode($value)
    {
        return $this->setParameter('billerCode', $value);
    }

    public function getCustomerReferenceNumber1()
    {
        return $this->getParameter('customerReferenceNumber1');
    }

    /**
     * Set customer configurable reference #1
     *
     * @param string $value  String, max 50 characters
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
     * @param string $value  String, max 50 characters
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
     * @param string $value  String, max 50 characters
     */
    public function setCustomerReferenceNumber3($value)
    {
        return $this->setParameter('customerReferenceNumber3', $value);
    }

    public function getHideBillerCode()
    {
        return $this->getParameter('hideBillerCode');
    }

    /**
     * Whether to hide the biller code from the end-user on the hosted checkout page
     *
     * @param bool $value
     */
    public function setHideBillerCode($value)
    {
        return $this->setParameter('hideBillerCode', $value);
    }

    public function getHideCustomerReferenceNumber1()
    {
        return $this->getParameter('hideCustomerReferenceNumber1');
    }

    /**
     * Whether to hide the customer configurable reference #1 from the end-user on the hosted checkout page
     *
     * @param bool $value
     */
    public function setHideCustomerReferenceNumber1($value)
    {
        return $this->setParameter('hideCustomerReferenceNumber1', $value);
    }

    public function getHideCustomerReferenceNumber2()
    {
        return $this->getParameter('hideCustomerReferenceNumber2');
    }

    /**
     * Whether to hide the customer configurable reference #2 from the end-user on the hosted checkout page
     *
     * @param bool $value
     */
    public function setHideCustomerReferenceNumber2($value)
    {
        return $this->setParameter('hideCustomerReferenceNumber2', $value);
    }

    public function getHideCustomerReferenceNumber3()
    {
        return $this->getParameter('hideCustomerReferenceNumber3');
    }

    /**
     * Whether to hide the customer configurable reference #3 from the end-user on the hosted checkout page
     *
     * @param bool $value
     */
    public function setHideCustomerReferenceNumber3($value)
    {
        return $this->setParameter('hideCustomerReferenceNumber3', $value);
    }

    /**
     * @deprecated  Alias. Use standard `getCreateToken()` instead.
     */
    public function getGenerateToken()
    {
        return $this->getCreateToken();
    }

    /**
     * Indicate whether or not to generate a token for the card used in the transaction
     *
     * @deprecated  Alias. Use standard `setCreateToken()` instead.
     *
     * @param bool $value  Generate a token or not
     */
    public function setGenerateToken($value)
    {
        return $this->setCreateToken($value);
    }

    public function getCustomerNumber()
    {
        return $this->getParameter('customerNumber');
    }

    /**
     * Set the unique customer ID in the merchant system
     *
     * @param string $value  Customer number to set
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
            'HppParameters' => array(
                'HideBillerCode' => (bool) $this->getHideBillerCode(),
                'HideCrn1' => (bool) $this->getHideCustomerReferenceNumber1(),
                'HideCrn2' => (bool) $this->getHideCustomerReferenceNumber2(),
                'HideCrn3' => (bool) $this->getHideCustomerReferenceNumber3(),
            ),
            'ProcessTxnData' => array(
                'Action' => $amount > 0 ? 'payment' : 'verify_only',
                'TestMode' => $this->getTestMode(),
                'Amount' => $this->getAmountInteger(),
                'BillerCode' => $this->getBillerCode(),
                'Crn1' => $this->getCustomerReferenceNumber1(),
                'Crn2' => $this->getCustomerReferenceNumber2(),
                'Crn3' => $this->getCustomerReferenceNumber3(),
                'Currency' => $this->getCurrency(),
                // 1 - no; 3 - always (don't leave it up to system or customer)
                'TokenisationMode' => $this->getCreateToken() ? 3 : 1,
                'MerchantReference' => $this->getTransactionId(),
                'SubType' => 'single',
                'Type' => 'internet',
            ),
            'RedirectionUrl' => $this->getReturnUrl(),
            'WebHookUrl' => $this->getNotifyUrl(),
        );
        if ($this->getCancelUrl()) {
            $data['HppParameters']['ReturnBarLabel'] = 'Cancel';
            $data['HppParameters']['ReturnBarUrl'] = $this->getCancelUrl();
        }
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

        // add stored card token if available
        if ($this->getToken() || $this->getCardReference()) {
            $data['ProcessTxnData']['DVTokenData'] = array(
                'DVToken' => $this->getToken() ?? $this->getCardReference(),
                'UpdateDVTokenExpiryDate' => false,
            );
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
