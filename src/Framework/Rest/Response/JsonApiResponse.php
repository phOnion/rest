<?php declare(strict_types=1);

namespace Onion\Framework\Rest\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Psr\Link\EvolvableLinkInterface;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;

class JsonApiResponse extends Response
{
    use JsonResponse;

    public function __construct(EntityInterface $entity, $status = 200, array $headers = [])
    {
        $payload = !$entity->isError() ? $this->convert($entity, true) : [
            'id' => (string) $entity->getDataItem('id'),
            'links' => $this->processLinks($entity->getLinks()),
            'status' => $status,
            'title' => $entity->getDataItem('title'),
            'detail' => $entity->getDataItem('detail'),
            'source' => $entity->getDataItem('source'),
            'meta' => $entity->getMetaData()
        ];

        $headers['content-type'] = 'application/vnd.api+json';
        parent::__construct($status, $headers, stream_for($this->encode($payload)));
    }

    /**
     * @param EvolvableLinkInterface[] $links
     *
     * @return array
     */
    private function processLinks(array $links): array
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

    private function convert(EntityInterface $entity, bool $isRoot = false): array
    {
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

        $payload['links'] = $this->processLinks($entity->getLinks());
        if ($entity->getDataItem('id', false)) {
            $payload = array_merge($payload, [
                'id' => (string) $entity->getDataItem('id'),
                'type' => $meta['@type']
            ]);

            $entity = $entity->withoutDataItem('id');

            if (!empty($entity->getData())) {
                $payload = array_merge_recursive($payload, [
                    'attributes' => $entity->getData()
                ]);
            }
        } else {
            $payload = array_merge($payload, [
                'data' => array_map(function ($item) {
                    return $this->convert($item);
                }, array_values($entity->getEmbedded()))
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
                if ($embeds !== []) {
                    $payload = ['data' => $payload];
                    $payload['included'] = isset($embeds[0]) ? $embeds : [$embeds];
                }
            }
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return $payload;
    }
}