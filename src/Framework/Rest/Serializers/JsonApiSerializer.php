<?php declare(strict_types = 1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Rest\Entity;
use Onion\Framework\Rest\Interfaces\SerializerInterface;
use Psr\Link\EvolvableLinkInterface;

class JsonApiSerializer extends PlainJsonSerializer implements SerializerInterface
{

    public function getContentType(): string
    {
        return 'application/vnd.api+json';
    }

    public function supports(Accept $accept): bool
    {
        return $accept->supports(
            $this->getContentType()
        );
    }

    /**
     * @param EvolvableLinkInterface[] $links
     * @param array                    $data
     *
     * @return array
     */
    private function processLinks(array $links, array $data): array
    {
        $collection = [];
        foreach ($links as $link) {
            $rel = implode(',', $link->getRels());
            if ($rel === 'curies') {
                continue;
            }
            $link = $link->withHref(str_replace(
                array_map(function ($value) {
                    return "{{$value}}";
                }, array_keys($data)),
                array_filter(array_values($data), function ($value) {
                    return is_string($value);
                }),
                $link->getHref()
            ));

            if (($attributes = $link->getAttributes()) !== []) {
                $collection[$rel] = [
                    'href' => $link->getHref(),
                    'meta' => $attributes
                ];
            } else {
                $collection[$rel] = $link->getHref();
            }
        }

        return $collection;
    }

    private function convertError(Entity $entity): array
    {
        return array_filter($entity->getData(), function ($value) {
            return $value !== null;
        });
    }

    protected function convert(Entity $entity): array
    {
        if ($entity->isError()) {
            return $this->convertError($entity);
        }

        $payload = [];

        if (($meta = $entity->getMeta()) !== []) {
            $payload['meta'] = $meta;
        }

        if ($entity->getLinksByRel('self') === []) {
            throw new \RuntimeException(
                'Entity mappings, must have "self" link'
            );
        }

        $payload['links'] = $this->processLinks($entity->getLinks(), $entity->getData());
        $payload = array_merge($payload, [ 'data' => [
            'id' => (string) $entity->getDataItem('id'),
            'type' => $entity->getDataItem('type')
        ]]);
        $entity = $entity->withoutDataItem('id')
            ->withoutDataItem('type');
        $payload = array_merge_recursive($payload, [ 'data' => [
            'attributes' => $entity->getData()
        ]]);

        foreach ($entity->getEmbedded() as $rel => $values) {
            if (!isset($payload['data']['relationships'])) {
                $payload['data']['relationships'] = [];
            }

            if (!isset($payload['data']['relationships'][$rel])) {
                $payload['data']['relationships'][$rel] = [];
            }

            $embeds = array_map([$this, 'convert'], $values);

            $payload['data']['relationships'][$rel][] = [
                'links' => $embeds[0]['links'],
                'data' => array_map(function ($embed) {
                    return [
                        'id' => (string) $embed['data']['id'],
                        'type' => $embed['data']['type']
                    ];
                }, $embeds)
            ];
            $payload = array_merge_recursive($payload, [
                'included' => $embeds
            ]);
        }

        return $payload;
    }
}
