<?php

namespace Onion\Framework\Rest\Transformers;

use Onion\Framework\Rest\Interfaces\EntityInterface;

use function Onion\Framework\merge;

class LinkedDataTransformer
{
    const ALLOWED_META_KEYS = [
        '@vocab',
        '@base',
    ];

    public function transform(EntityInterface $entity): array
    {
        $data['@context'] = $this->getMeta($entity->getMetaData());
        $data['@type'] = $entity->getRel();
        $data = merge($data, $this->getLinks(
            $entity->getLinks(),
            (array) $entity->getData(),
            $data['@context']['@base'] ?? ''
        ));

        if ($entity->hasEmbedded()) {
            $data = merge($data, $this->getEmbedded($entity->getEmbedded(), true));
        }

        return merge($data, (array) $entity->withoutDataItem('id')->getData());
    }

    private function getMeta(iterable $data): array
    {
        $meta = [];
        foreach ($data as $key => $value) {
            if (substr($key, 0, 1) === '@' && !in_array($key, static::ALLOWED_META_KEYS)) {
                continue;
            }

            $meta[$key] = $value;
        }

        return $meta;
    }

    private function getLinks(iterable $data, array $props, string $base = ''): array
    {
        $links = [];
        $base = rtrim($base, '/');

        foreach ($data as $link) {
            if ($link->isTemplated()) {
                continue;
            }

            $href = preg_replace_callback(
                '~({([^}]+)})~i',
                function ($matches) use ($props) {
                    return $props[$matches[2]] ?? $matches[1];
                },
                $base . $link->getHref()
            );

            foreach ($link->getRels() as $rel) {
                if ($rel === 'self') {
                    $links['@id'] = $href;

                    continue 2;
                }

                $links[$rel] = $href;
            }
        }

        return $links;
    }

    private function getEmbedded(iterable $embedded, bool $includeNested = false): array
    {
        if (!$includeNested) {
            return [];
        }
        $data = [];
        foreach ($embedded as $entity) {
            $data[$entity->getRel()][] = $this->transform($entity->withoutDataItem('id'));
        }

        return $data;
    }
}
