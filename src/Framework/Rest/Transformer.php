<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Fig\Link\Link;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface as Hydratable;
use Onion\Framework\Rest\Interfaces\EntityInterface as IEntity;
use Onion\Framework\Rest\Interfaces\SerializableInterface;
use Onion\Framework\Rest\Interfaces\TransformerInterface;

class Transformer implements TransformerInterface
{
    private $mappings;
    public function __construct(array $mappings = [])
    {
        $this->mappings = $mappings;
    }

    public function transform(Hydratable $hydratableInterface, array $includes = [], array $fields = []): IEntity
    {
        if (!$hydratableInterface instanceof SerializableInterface) {
            $class = get_class($hydratableInterface);
            if (!isset($this->mappings[$class])) {
                throw new \InvalidArgumentException(sprintf(
                    'No mappings available for "%s"',
                    $class
                ));
            }

            $mapping = &$this->mappings[$class];
        } else {
            $mapping = $hydratableInterface->getMappings();
        }


        if ($fields !== [] || (isset($fields[$mapping['rel']]) && $fields[$mapping['rel']] !== [])) {
            $data = $hydratableInterface->extract(array_intersect($fields[$mapping['rel']], $mapping['fields']));
        } else {
            $data = $hydratableInterface->extract($mapping['fields'] ?? []);
        }

        array_walk($data, function (&$value) use ($fields, $includes) {
            if (is_array($value)) {
                foreach ($value as $index => $item) {
                    if ($item instanceof Hydratable) {
                        $value[$index] = $this->transform($item, $includes, $fields);
                    }
                }
            }
        });

        $entity = (new Entity($mapping['rel']))->withData($data)->withMetaData($mapping['meta'] ?? []);
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

            foreach ($relations as $relation => $method) {
                $result = $hydratableInterface->{$method}();
                if (is_array($result) || ($result instanceof \Traversable)) {
                    foreach ($result as $embedded) {
                        $entity = $entity->addEmbedded(
                            $relation,
                            $this->transform($embedded, $includes, $fields),
                            true
                        );
                    }
                    continue;
                }

                $entity = $entity->addEmbedded(
                    $relation,
                    $this->transform($result, $includes, $fields),
                    false
                );
            }
        }

        return $entity;
    }
}
