<?php

namespace Omnipay\BPOINT\Message;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Accept an incoming notification (a webhook)
 *
 * @see https://www.bpoint.com.au/developers/v3/#!#webhooks
 */
class WebhookNotification extends AbstractRequest implements NotificationInterface, ResponseInterface
{
    /**
     * The data contained in the response.
     *
     * @var mixed
     */
    protected $data;

    /**
     * @inheritDoc
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest)
    {
        parent::__construct($httpClient, $httpRequest);
        // fetch POST stream directly
        $this->data = json_decode($httpRequest->getContent(), true);
    }

    /**
     * ResponseInterface implemented so that we can return self here for any legacy support that uses send()
     */
    public function sendData($data)
    {
        return $this;
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
     * Was the transaction successful?
     *
     * @return string Transaction status, one of {@link NotificationInterface::STATUS_COMPLETED},
     * {@link NotificationInterface::STATUS_PENDING}, or {@link NotificationInterface::STATUS_FAILED}.
     */
    public function getTransactionStatus()
    {
        if (!isset($this->data['ResponseCode'])) {
            return NotificationInterface::STATUS_FAILED;
        }
        if ($this->data['ResponseCode'] == '0') {
            return NotificationInterface::STATUS_COMPLETED;
        }
        if ($this->data['ResponseCode'] == 'P') {
            return NotificationInterface::STATUS_PENDING;
        }

        // last resort, assume failure
        return NotificationInterface::STATUS_FAILED;
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

    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the original request which generated this response
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this;
    }

    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->getTransactionStatus() == NotificationInterface::STATUS_COMPLETED;
    }

    /**
     * Does the response require a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return false;
    }

    /**
     * Is the transaction cancelled by the user?
     *
     * @return boolean
     */
    public function isCancelled()
    {
        return isset($this->data['ResponseCode']) && $this->data['ResponseCode'] == 'C';
    }

    /**
     * Response code
     *
     * @return null|string A response code from the payment gateway
     */
    public function getCode()
    {
        return isset($this->data['ResponseCode']) ? $this->data['ResponseCode'] : null;
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
