<?php

namespace Omnipay\BPOINT\Message;

/**
 * BPOINT Complete Purchase Request
 *
 * Example response:
 * http://example.com/txnreceipt?ResponseCode=0&ResponseText=Success&ResultKey=13cfa799-8278-4872-a705-7ed49d516c0b
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    /** @see PurchaseRequest::$action */
    protected $action = 'withauthkey';

    public function getData()
    {
        return $this->httpRequest->request->all() + $this->httpRequest->query->all();
    }

    /**
     * Make request for additional details as per {@see https://www.bpoint.com.au/developers/v3/#!#threepartyrettranres}
     */
    public function sendData($data)
    {
        // if we have a valid API response and a result key, let's get more detail about the transaction
        if (isset($data['ResponseCode']) && $data['ResponseCode'] == 0 && isset($data['ResultKey'])) {
            // submit request with the returned result key
            $httpResponse = $this->httpClient->request(
                'GET',
                $this->getEndpoint()."/".$data['ResultKey'],
                ['Authorization' => $this->getAuthHeader()]
            );
            // get response data
            $responseData = json_decode($httpResponse->getBody()->getContents(), true);
            $data = array_merge($data, $responseData['TxnResp']);
        }
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
