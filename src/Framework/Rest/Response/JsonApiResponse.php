<?php declare(strict_types=1);

namespace Onion\Framework\Rest\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Psr\Link\EvolvableLinkInterface;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;

class JsonApiResponse extends Response
{
    const RESPONSE_TYPE_DATA = 'data';
    const RESPONSE_TYPE_INFO = 'info';
    const RESPONSE_TYPE_ERROR = 'error';

    private $responseType;

    use JsonResponse;

    public function __construct(
        EntityInterface $entity,
        $status = 200,
        array $headers = [],
        string $responseType = self::RESPONSE_TYPE_DATA
    ) {
        $this->responseType = $responseType;
        $payload = $this->encode($this->convert($entity, true));

        $headers['content-type'] = 'application/vnd.api+json';
        parent::__construct($status, $headers, stream_for($payload));
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

    private function convert(EntityInterface $entity): array
    {
        if ($this->responseType === self::RESPONSE_TYPE_ERROR) {
            return $this->handleError($entity);
        }

        if ($this->responseType === self::RESPONSE_TYPE_INFO) {
            return $this->handleMeta($entity);
        }

        return $this->handleSuccess($entity);
    }

    private function handleError(EntityInterface $entity)
    {
        $errors = [];
        foreach ($entity->getEmbedded() as $item) {
            $errors[] = array_merge(
                $item->getData(),
                $this->handleMeta($item),
                ['status' => (string) parent::getStatusCode()]
            );
        }

        return array_merge(['errors' => $errors], $this->handleMeta($entity));
    }

    private function handleSuccess(EntityInterface $entity): array
    {
        $payload = [];

        if ($entity->getDataItem('id', false)) {
            $payload = array_merge($payload, [
                'id' => (string) $entity->getDataItem('id'),
            ]);

            $entity = $entity->withoutDataItem('id');
        } else {
            if ($entity->hasEmbedded()) {
                foreach ($entity->getEmbedded() as $embed) {
                    $payload = array_merge($payload, $this->convert($embed));
                }
            }
        }

        if (!empty($entity->getData())) {
            $payload['attributes'] = $entity->getData();
        }


        if ($entity->hasEmbedded()) {
            $embeds = [];
            foreach ($entity->getEmbedded() as $value) {
                if (!isset($payload['relationships'])) {
                    $payload['relationships'] = [];
                }

                if (!isset($payload['relationships'][$value->getRel()])) {
                    $payload['relationships'][$value->getRel()] = [];
                }

                $embed = $this->convert($value);
                $payload['relationships'][$value->getRel()][] = [
                    'links' => $embed['links'],
                    'data' => [
                        'id' => (string) $embed['id'],
                            'type' => $embed['type']
                    ]
                ];
                unset($embed['links']);
                $embeds[] = $embed;
            }

            if ($embeds !== []) {
                $payload = [
                    'data' => [$payload],
                    'included' => $embeds,
                ];
            }
        }


        $payload = array_merge($payload, $this->handleMeta($entity));

        return $payload;
    }

    private function handleMeta(EntityInterface $entity): array
    {
        $meta = $entity->getMetaData();

        if (isset($meta['api'])) {
            $meta = $meta['api'];
        }

        $payload = [];

        if ($this->responseType !== self::RESPONSE_TYPE_ERROR) {
            if ($entity->getLinksByRel('self') === []) {
                throw new \RuntimeException(
                    'Entity mappings, must have "self" link'
                );
            }

            assert(
                array_key_exists('@type', $meta),
                new \RuntimeException('Missing meta key "@type" for rel: ' . $entity->getRel())
            );

            $payload['type'] = $meta['@type'];
            if (isset($meta['@type'])) {
                unset($meta['@type']);
            }
        }

        if (!empty($meta)) {
            $payload['meta'] = (object) $meta;
        }

        $payload['links'] = $this->processLinks($entity->getLinks());

        return $payload;
    }
}
