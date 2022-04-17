<?php

namespace Onion\Framework\Rest\Responses\Json;

use GuzzleHttp\Psr7\Response as HTTPResponse;
use Onion\Framework\Json;

class Response extends HTTPResponse
{
    public function __construct(
        int $status = 200,
        array $headers = [],
        mixed $body = null,
        string $version = '1.1',
        ?string $reason = null
    ) {
        $headers['content-type'] = 'application/json';

        parent::__construct(
            $status,
            $headers,
            $body === null ? null : Json::encode($body),
            $version,
            $reason
        );
    }
}
