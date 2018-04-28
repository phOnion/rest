<?php declare(strict_types=1);

namespace Onion\Framework\Rest\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;

class JsonPlainResponse extends Response
{
    use JsonResponse;

    public function __construct(EntityInterface $entity, $status = 200, array $headers = [])
    {
        $headers['content-type'] = 'application/json';
        $payload = $this->encode($this->convert($entity));
        parent::__construct($status, $headers, stream_for($payload));
    }

    protected function convert(EntityInterface $entity): array
    {
        $data = array_map(function ($entity) {
            if (is_array($entity)) {
                foreach ($entity as $index => $item) {
                    if ($item instanceof EntityInterface) {
                        $entity[$index] = $this->convert($item);
                    }
                }
            }

            if ($entity instanceof EntityInterface) {
                $entity = $this->convert($entity);
            }
            return $entity;
        }, $entity->getData());

        foreach ($entity->getEmbedded() as $rel => $relation) {
            if (!isset($data[$rel])) {
                if (is_array($relation)) {
                    $data[$rel] = array_map([$this, 'convert'], $relation);
                }

                if ($relation instanceof EntityInterface) {
                    $data[$rel] = $this->convert($relation);
                }
            }
        }

        return $data;
    }
}
