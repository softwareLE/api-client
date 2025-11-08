<?php

declare(strict_types=1);

namespace CubeSystems\ApiClient\Client\Responses;

use CodeDredd\Soap\Client\Response as BaseResponse;
use GuzzleHttp\TransferStats;

class SoapResponseDecorator extends BaseResponse
{
    public ?BaseResponse $original = null;

    public function __construct(BaseResponse $response)
    {
        $this->original = $response;
    }

    public function getTransferStats(): ?TransferStats
    {
        return $this->original->transferStats;
    }
}
