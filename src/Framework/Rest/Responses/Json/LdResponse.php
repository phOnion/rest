<?php
namespace Onion\Framework\Rest\Responses\Json;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Rest\Interfaces\TransformableInterface;
use Onion\Framework\Rest\Transformers\Traits\Ld;

use function Onion\Framework\Common\merge;

class LdResponse extends Response
{
    use Json;
    use Ld;

    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        $headers['content-type'] = 'application/ld+json';

        if ($body instanceof TransformableInterface) {
            $body = $this->getTransformer()->transform(
                $body->transform()
            );
        }

        parent::__construct(
            $status,
            $headers,
            $this->encode($body),
            $version,
            $reason
        );
    }
}
