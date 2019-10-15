<?php

namespace Omnipay\BPOINT;

use Omnipay\Common\AbstractGateway;
use Omnipay\BPOINT\Message\CompletePurchaseRequest;
use Omnipay\BPOINT\Message\PurchaseRequest;

/**
 * BPOINT Redirect Gateway
 *
 * @link https://www.bpoint.com.au/developers/v3/
 */
class RedirectGateway extends AbstractGateway
{
    public function getName()
    {
        return 'BPOINT Redirect';
    }

    public function getDefaultParameters()
    {
        return array(
            'username' => '',
            'password' => '',
            'merchantNumber' => '',
            'merchantShortName' => '',
            'testMode' => false,
        );
    }

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

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\BPOINT\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\BPOINT\Message\CompletePurchaseRequest', $parameters);
    }
}
