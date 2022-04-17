<?php

namespace Onion\Framework\Rest\Responses\Json;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Transformers\LinkedDataTransformer;

class LinkedDataResponse extends Response
{
    public function __construct(
        int $status = 200,
        array $headers = [],
        ?EntityInterface $body = null,
        string $version = '1.1',
        ?string $reason = null
    ) {
        $headers['content-type'] = 'application/ld+json';

        parent::__construct(
            $status,
            $headers,
            $body === null ? null : (new LinkedDataTransformer())->transform($body),
            $version,
            $reason
        );
    }
}
