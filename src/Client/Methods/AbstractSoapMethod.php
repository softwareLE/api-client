<?php

declare(strict_types=1);

namespace CubeSystems\ApiClient\Client\Methods;

use CodeDredd\Soap\Client\Response as RawResponse;
use CubeSystems\ApiClient\Client\Contracts\CacheStrategy;
use CubeSystems\ApiClient\Client\Contracts\Payload;
use CubeSystems\ApiClient\Client\Contracts\Response;
use CubeSystems\ApiClient\Client\Plugs\PlugManager;
use CubeSystems\ApiClient\Client\Services\AbstractSoapService;
use CubeSystems\ApiClient\Events\ApiCalled;
use CubeSystems\ApiClient\Client\Responses\SoapResponseDecorator;

abstract class AbstractSoapMethod extends AbstractMethod
{
    public function __construct(
        // more specific type
        AbstractSoapService $service,
        CacheStrategy $cacheStrategy,
        PlugManager $plugManager,
    ) {
        parent::__construct($service, $cacheStrategy, $plugManager);
    }

    final protected function retrieveFromRemote(Payload $payload): Response
    {
        $microtimeFrom = microtime(true);

        $rawResponse = $this->getService()->getClient()->call(
            $this->getName(),
            $payload->toArray()
        );

        $extendedResponse = app(SoapResponseDecorator::class, ['response' => $rawResponse]);

        $microtimeTo = microtime(true);

        $rawDataArray = $this->getRawDataArray($rawResponse);

        $response = $this->toResponse($rawDataArray, [], $rawResponse->status());

        $this->dispatchServiceCalledEvent(
            $payload,
            $extendedResponse,
            $response,
            $microtimeFrom,
            $microtimeTo
        );

        return $response;
    }

    private function dispatchServiceCalledEvent(
        Payload $payload,
        SoapResponseDecorator $rawResponse,
        Response $response,
        float $microtimeFrom,
        float $microtimeTo
    ): void {

        $stats = $this->makeCallStats(
            $rawResponse->getTransferStats(),
            $microtimeFrom,
            $microtimeTo
        );

        ApiCalled::dispatch(
            $this,
            $payload,
            $response,
            $stats
        );
    }

    private function getRawDataArray(RawResponse $rawResponse): array
    {
        $rawDataArray = $rawResponse->json();

        if (is_array($rawDataArray)) {
            return $rawDataArray;
        }

        return [
            'status' => [
                'code' => 'T',
                'message' => $rawResponse->body()
            ]
        ];
    }

    public function getService(): AbstractSoapService
    {
        /** @var AbstractSoapService $service */
        $service = $this->service;

        return $service;
    }
}
