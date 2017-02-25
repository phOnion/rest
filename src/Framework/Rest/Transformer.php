<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Fig\Link\Link;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface;

class Transformer
{
    private $mappings = [];
    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function transform(HydratableInterface $hydratableInterface, array $includes = [], array $fields = []): Entity
    {
        $class = get_class($hydratableInterface);
        if (!isset($this->mappings[$class])) {
            throw new \InvalidArgumentException(sprintf(
                'No mappings available for "%s"',
                $class
            ));
        }

        $mapping = &$this->mappings[$class];
        if ($fields !== [] && isset($fields[$class])) {
            $data = $hydratableInterface->extract(array_intersect($fields[$class], $mapping['fields']));
        } else {
            $data = $hydratableInterface->extract($mapping['fields']);
        }

        $entity = (new Entity($mapping['rel']))->withData($data);
        foreach ($mapping['links'] as $link) {
            $lnk = new Link($link['rel'], $link['href']);
            foreach ($link as $attr => $value) {
                if ($attr === 'rel' || $attr === 'href') {
                    continue;
                }

                $lnk = $lnk->withAttribute($attr, $value);
            }
            $entity = $entity->withLink($lnk);
        }

        if (isset($mapping['relations'])) {
            $relations = $mapping['relations'];
            if ($includes !== []) {
                $relations = array_intersect(array_keys($mapping['relations']), $includes);
            }

            foreach ($relations as $relation) {
                $result = $hydratableInterface->{$mapping['relations'][$relation]}();
                if (is_array($result)) {
                    foreach ($result as $embedded) {
                        $entity = $entity->addEmbedded($this->transform($embedded, $includes, $fields));
                    }

                    continue;
                }

                $entity = $this->transform($result, $includes, $fields);
            }
        }

        return $entity;
    }
}
