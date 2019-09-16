<?php
namespace Onion\Framework\Rest\Responses\Json;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Rest\Interfaces\TransformableInterface;
use Onion\Framework\Rest\Transformers\Traits\Hal;

use function Onion\Framework\Common\merge;

class HalResponse extends Response
{
    use Json;
    use Hal;

    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        $headers['content-type'] = 'application/json+hal';

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
