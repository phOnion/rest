<?php declare(strict_types=1);
namespace Onion\Framework\Rest\Response;

use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Onion\Framework\Rest\Response\JsonResponse;
use Onion\Framework\Rest\Interfaces\EntityInterface;

class JsonHalResponse extends Response
{
    use JsonResponse;

    public function __construct(EntityInterface $entity, int $status = 200, array $headers = [])
    {
        $headers['content-type'] = 'application/hal+json';
        $payload = $this->encode($this->convert($entity, true));
        parent::__construct($status, $headers, stream_for($payload));
    }

    /**
     * @param EvolvableLinkInterface[] $links
     * @param array $data
     *
     * @return array
     */
    private function processLinks(array $links): array
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

    private function convert(EntityInterface $entity): array
    {
        $payload = [];
        if ($entity->getLinksByRel('self') === []) {
            throw new \RuntimeException(
                'Entity mappings, must have "self" link'
            );
        }

        $payload['_links'] = $this->processLinks($entity->getLinks(), $entity->getData());
        $payload = array_merge($payload, $entity->getData());

        if ($entity->hasEmbedded()) {
            foreach ($entity->getEmbedded() as $value) {
                if (!isset($payload['_embedded'])) {
                    $payload['_embedded'] = [];
                }

                $payload['_embedded'][$value->getRel()][] = $this->convert($value);
            }
        }

        return $payload;
    }
}
