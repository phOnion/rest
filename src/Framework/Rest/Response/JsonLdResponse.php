<?php declare(strict_types=1);

namespace Onion\Framework\Rest\Response;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Psr\Link\EvolvableLinkInterface;
use Zend\Diactoros\Response\InjectContentTypeTrait;
use Zend\Diactoros\Response\JsonResponse;

class JsonLdResponse extends JsonResponse
{
    use InjectContentTypeTrait;

    public function __construct(EntityInterface $entity, $status = 200, array $headers = [], $encodingOptions = self::DEFAULT_JSON_FLAGS)
    {
        parent::__construct(
            $this->convert($entity, true),
            $status,
            $this->injectContentType('application/ld+json', $headers)
        );
    }

    private function convert(EntityInterface $entity, bool $isRoot = false): array
    {
        $payload = [
            '@context' => []
        ];

        $meta = $entity->getMetaData();

        if (isset($meta['ld'])) {
            $meta = $meta['ld'];
        }

        $payload['@type'] = $entity->getRel();

        if ($entity->getLinksByRel('self') !== []) {
            $payload['@id'] = rtrim($meta['@base'] ?? '', '/') . str_replace(
                    array_map(function ($value) {
                        return "{{$value}}";
                    }, array_keys($entity->getData())),
                    array_values($entity->getData()),
                    array_values($entity->getLinksByRel('self'))[0]->getHref()
                );
        }

        if (count($meta) === 1 && isset($meta['@vocab'])) {
            $payload['@context'] = array_pop($meta);
        } else {
            $payload['@context'] = array_filter($meta, function ($index) {
                if (strpos($index, '@') === 0) {
                    return in_array($index, ['@vocab', '@base'], true);
                }

                return true;
            }, ARRAY_FILTER_USE_KEY);
        }

        foreach ($entity->getLinks() as $link) {
            if (!in_array('self', $link->getRels())) {
                array_map(function (string $rel) use ($link, &$payload, $meta) {
                    /** @var EvolvableLinkInterface $link */
                    $link = $link->withHref(rtrim($meta['@base'] ?? '', '/') . $link->getHref());

                    if (!$link->isTemplated()) {
                        $payload[$rel] = $link->getHref();
                    }
                }, $link->getRels());
            }
        }

        $entity = $entity->withoutDataItem('id');

        if ($isRoot) {
            foreach ($entity->getEmbedded() as $rel => $embed) {
                if (is_array($embed)) {
                    $payload[$rel] = array_map([$this, 'convert'], $embed);
                    continue;
                }

                $payload[$rel] = $this->convert($embed);
            }
        }

        return array_merge($entity->getData(), $payload);
    }
}
