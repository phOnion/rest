<?php
namespace Onion\Framework\Rest\Transformers;

use function Onion\Framework\Common\merge;
use Onion\Framework\Common\Collection\Collection;
use Onion\Framework\Rest\Interfaces\EntityInterface;

use Psr\Link\LinkInterface;

class HalTransformer
{
    public function transform(EntityInterface $entity): array
    {
        if (empty($entity->getLinksByRel('self'))) {
            throw new \LogicException('Provided entity representation does not contain as `self` link');
        }

        $data = [];
        $data['_links'] = $this->getLinks($entity->getLinks());
        $embedded = $this->getEmbedded(
            $entity->hasEmbedded() ? $entity->getEmbedded() : [],
            true
        );

        if (!empty($embedded)) {
            $data['_embedded'] = $embedded;
        }

        return merge($data, (array) $entity->getData());
    }

    private function getLinks(iterable $entityLinks): array
    {
        $raw = (new Collection($entityLinks))
            ->map(function (LinkInterface $link) {
                return merge([
                    'href' => $link->getHref(),
                    'templated' => $link->isTemplated(),
                    'rels' => $link->getRels(),
                ], $link->getAttributes());
            });

        $links = [];
        foreach ($raw as $link) {
            $key = implode(',', $link['rels']);
            unset($link['rels']);
            $links[$key] = $link;
        }

        return $links;
    }

    private function getEmbedded(iterable $embedded, bool $includeNested = false)
    {
        if (!$includeNested) {
            return [];
        }
        $data = [];
        foreach ($embedded as $item) {
            $item = $item->transform();
            $data[$item->getRel()][] = $this->transform($item);
        }

        return $data;
    }
}
