<?php declare(strict_types = 1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Accept;
use Onion\Framework\Rest\Entity;

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

    protected function convert(Entity $entity): array
    {
        $payload = [
            '@context' => []
        ];

        $meta = $entity->getMeta();
        $payload['@type'] = $entity->getRel();

        if ($entity->getLinksByRel('self') !== []) {
            $payload['@id'] = str_replace(
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
            $payload['@context'] = array_filter($meta, function ($index) {
                if (strpos($index, '@') === 0) {
                    return in_array($index, ['@vocab', '@base'], true);
                }

                return strpos($index, 'ld-') === 0;
            }, ARRAY_FILTER_USE_KEY);
        }

        $entity = $entity->withoutDataItem('id');

        foreach ($entity->getEmbedded() as $rel => $embed) {
            $payload[$rel] = array_map([$this, 'convert'], $embed);
        }

        return array_merge($payload, $entity->getData());
    }
}
