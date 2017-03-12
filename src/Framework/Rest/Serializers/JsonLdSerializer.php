<?php declare(strict_types = 1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface as Accept;
use Onion\Framework\Rest\Interfaces\EntityInterface as Entity;
use Psr\Link\EvolvableLinkInterface;

class JsonLdSerializer extends PlainJsonSerializer
{
    public function getContentType(): string
    {
        return 'application/ld+json';
    }

    public function supports(Accept $accept): bool
    {
        return $accept->supports(
            $this->getContentType()
        );
    }

    protected function convert(Entity $entity, bool $isRoot = false): array
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
            $payload['@id'] = rtrim($meta['@context']['@base'] ?? '', '/') . str_replace(
                    array_map(function ($value) {
                        return "{{$value}}";
                    }, array_keys($entity->getData())),
                    array_values($entity->getData()),
                    array_values($entity->getLinksByRel('self'))[0]->getHref()
                );
        }

        if (count($meta) === 1) {
            $payload['@context'] = array_pop($meta);
        } else {
            $payload['@context'] = array_filter($meta, function ($index) use ($entity) {
                if (strpos($index, '@') === 0) {
                    return in_array($index, ['@vocab', '@base'], true);
                }

                return true;
            }, ARRAY_FILTER_USE_KEY);
        }

        foreach ($entity->getLinks() as $link) {
            if (!in_array('self', $link->getRels())) {
                array_map(function (string $rel) use ($entity, $link, &$payload, $meta) {
                    /** @var EvolvableLinkInterface $link */
                    $link = $link->withHref(rtrim($meta['@context']['@base'] ?? '', '/') . str_replace(
                            array_map(function ($key) {
                                return "{{$key}}";
                            }, array_keys($entity->getData())),
                            array_values($entity->getData()),
                            $link->getHref()
                        ));

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

        return array_merge($payload, array_map(function($entity) {
            if (is_array($entity)) {
                foreach ($entity as $index => $item) {
                    if ($item instanceof Entity) {
                        $entity[$index] = $this->convert($item);
                    }
                }
            }

            if ($entity instanceof Entity) {
                $entity = $this->convert($entity);
            }

            return $entity;
        }, $entity->getData()));
    }
}
