<?php
namespace Onion\Framework\Rest\Responses\Json;

use GuzzleHttp\Psr7\Response;
use Onion\Framework\Rest\Interfaces\TransformableInterface;

use function Onion\Framework\Common\merge;

class PlainResponse extends Response
{
    use Json;

    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
        $headers['content-type'] = 'application/json';

        if ($body instanceof TransformableInterface) {
            $body = $body->transform()->getData();
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
