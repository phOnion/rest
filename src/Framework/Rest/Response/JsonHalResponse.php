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
        $payload = $this->encode($this->convert($entity));
        parent::__construct($status, $headers, stream_for($this->encode($payload)));
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

    private function convert(EntityInterface $entity, bool $isRoot = false): array
    {
        $payload = [];
        if ($entity->getLinksByRel('self') === []) {
            throw new \RuntimeException(
                'Entity mappings, must have "self" link'
            );
        }

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
