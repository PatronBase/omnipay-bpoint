<?php

namespace Omnipay\BPOINT\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Exception\InvalidResponseException;

/**
 * BPOINT Complete Purchase Response
 */
class CompletePurchaseResponse extends AbstractResponse
{
    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return isset($this->data['ResponseCode']) && $this->data['ResponseCode'] == 0;
    }

    /**
     * Get the authorisation code if available.
     *
     * @return null|string
     */
    public function getTransactionReference()
    {
        return isset($this->data['AuthoriseId']) ? $this->data['AuthoriseId'] : null;
    }

    /**
     * Get the merchant response message if available.
     *
     * @return null|string
     */
    public function getMessage()
    {
        return isset($this->data['ResponseText']) ? $this->data['ResponseText'] : null;
    }

    /**
     * Get the card type if available.
     *
     * @return null|string
     */
    public function getCardType()
    {
        return isset($this->data['CardType']) ? $this->data['CardType'] : null;
    }
}
