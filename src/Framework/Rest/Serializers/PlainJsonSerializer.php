<?php declare(strict_types = 1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Rest\Entity;
use Onion\Framework\Rest\Interfaces\SerializerInterface;

class PlainJsonSerializer implements SerializerInterface
{

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function supports(Accept $accept): bool
    {
        return $accept->supports('application/json') ||
            $accept->supports('text/json');
    }

    protected function convert(Entity $entity): array
    {
        return $entity->getData();
    }

    public function serialize(Entity $entity): string
    {
        return json_encode(
            $this->convert($entity),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }
}
