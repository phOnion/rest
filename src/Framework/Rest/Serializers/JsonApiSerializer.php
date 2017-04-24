<?php declare(strict_types=1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface as Accept;
use Onion\Framework\Rest\Interfaces\EntityInterface as Entity;
use Psr\Link\EvolvableLinkInterface;

class JsonApiSerializer extends PlainJsonSerializer
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

    protected function convert(Entity $entity, bool $isRoot = false): array
    {
        if ($entity->isError()) {
            return $this->convertError($entity);
        }

        $payload = [];
        $meta = $entity->getMetaData();

        if (isset($meta['api'])) {
            $meta = $meta['api'];
        }

        assert(
            array_key_exists('@type', $meta),
            new \RuntimeException('Missing meta key "@type" for rel: ' . $entity->getRel())
        );

        if ($entity->getLinksByRel('self') === []) {
            throw new \RuntimeException(
                'Entity mappings, must have "self" link'
            );
        }

        $payload['links'] = $this->processLinks($entity->getLinks(), $entity->getData());
        if ($entity->getDataItem('id', false)) {
            $payload = array_merge($payload, [
                'id' => (string) $entity->getDataItem('id'),
                'type' => $meta['@type']
            ]);

            $entity = $entity->withoutDataItem('id');

            $payload = array_merge_recursive($payload, [
                'attributes' => $entity->getData()
            ]);
        } else {
            $payload = array_merge($payload, [
                'data' => array_map(function ($item) {
                    return $this->convert($item);
                }, array_values($entity->getData())[0])
            ]);
        }

        if (isset($meta['@type'])) {
            unset($meta['@type']);
        }

        foreach ($entity->getEmbedded() as $rel => $values) {
            if (!isset($payload['relationships'])) {
                $payload['relationships'] = [];
            }

            if (!isset($payload['relationships'][$rel])) {
                $payload['relationships'][$rel] = [];
            }

            if (is_array($values)) {
                $embeds = array_map(function ($embed) {
                    return $this->convert($embed);
                }, $values);


                $payload['relationships'][$rel][] = [
                    'links' => $embeds[0]['links'],
                    'data' => array_map(function ($embed) {
                        return [
                            'id' => (string)$embed['id'],
                            'type' => $embed['type']
                        ];
                    }, $embeds)
                ];
            } else {
                $embeds = $this->convert($values);
                $payload['relationships'][$rel] = [
                    'links' => $embeds['links'],
                    'data' => [
                        'id' => (string)$embeds['id'],
                        'type' => $embeds['type']
                    ]
                ];
            }

            if ($isRoot) {
                if ($embeds[0] !== []) {
                    $payload = array_merge_recursive($payload, [
                        'included' => $embeds
                    ]);
                }
            }
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return $payload;
    }
}
