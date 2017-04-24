<?php declare(strict_types=1);
namespace Onion\Framework\Rest\Serializers;

use Onion\Framework\Http\Header\Interfaces\AcceptInterface as Accept;
use Onion\Framework\Rest\Interfaces\EntityInterface as Entity;
use Psr\Link\EvolvableLinkInterface;

class HalJsonSerializer extends PlainJsonSerializer
{
    public function getContentType(): string
    {
        return 'application/hal+json';
    }

    public function supports(Accept $accept): bool
    {
        return $accept->supports(
            $this->getContentType()
        );
    }

    /**
     * @param EvolvableLinkInterface[] $links
     * @param array $data
     *
     * @return array
     */
    private function processLinks(array $links, array $data): array
    {
        $collection = [];
        foreach ($links as $link) {
            $rel = implode(',', $link->getRels());

            if (array_key_exists($rel, $collection)) {
                if (count($collection[$rel]) === count($collection[$rel], COUNT_RECURSIVE)) {
                    $collection[$rel] = [$collection[$rel]];
                }
                $collection[$rel][] = array_merge($link->getAttributes(), [
                    'href' => $link->getHref(),
                    'templated' => $link->isTemplated()
                ]);

                continue;
            }

            $collection[$rel] = array_merge($link->getAttributes(), [
                'href' => $link->getHref(),
                'templated' => $link->isTemplated()
            ]);
        }

        return $collection;
    }

    protected function convert(Entity $entity, bool $isRoot = false): array
    {
        $payload = [];
        if ($entity->getLinksByRel('self') === []) {
            throw new \RuntimeException(
                'Entity mappings, must have "self" link'
            );
        }

        $payload['_links'] = [];
        $payload['_links'] = $this->processLinks($entity->getLinks(), $entity->getData());
        $payload = array_merge($payload, $entity->getData());

        if ($isRoot) {
            foreach ($entity->getEmbedded() as $rel => $values) {
                if (!isset($payload['_embedded'])) {
                    $payload['_embedded'] = [];
                }


                if (is_array($values)) {
                    $payload['_embedded'][$rel] = array_map([$this, 'convert'], $values);
                    continue;
                }

                $payload['_embedded'][$rel] = [$this->convert($values)];
            }
        }

        return $payload;
    }
}
