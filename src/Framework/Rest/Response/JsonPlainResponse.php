<?php declare(strict_types=1);

namespace Onion\Framework\Rest\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Zend\Diactoros\Response\JsonResponse;

class JsonPlainResponse extends JsonResponse
{
    public function __construct(EntityInterface $entity, $status = 200, array $headers = [])
    {
        parent::__construct(
            $this->convert($entity, true),
            $status,
            $headers
        );
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
