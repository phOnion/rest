<?php declare(strict_types = 1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface as Accept;
use Onion\Framework\Rest\Interfaces\EntityInterface as Entity;
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
        $data = $entity->getData();
        foreach ($entity->getEmbedded() as $rel => $relation) {
            if (!isset($data[$rel])) {
                if (is_array($relation)) {
                    $payload[$rel] = array_map([$this, 'convert'], $relation);
                    continue;
                }

                $data[$rel] = $this->convert($relation);
            }
        }

        return $data;
    }

    public function serialize(Entity $entity): string
    {
        return json_encode(
            $this->convert($entity),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }
}
