<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Fig\Link\Link;
use Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Interfaces\TransformerInterface;

class Transformer implements TransformerInterface
{
    private $mappings = [];
    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    public function transform(HydratableInterface $hydratableInterface, array $includes = [], array $fields = []): EntityInterface
    {
        $class = get_class($hydratableInterface);
        if (!isset($this->mappings[$class])) {
            throw new \InvalidArgumentException(sprintf(
                'No mappings available for "%s"',
                $class
            ));
        }

        $mapping = &$this->mappings[$class];
        if ($fields !== [] || (isset($fields[$mapping['rel']]) && $fields[$mapping['rel']] !== [])) {
            $data = $hydratableInterface->extract(array_intersect($fields[$mapping['rel']], $mapping['fields']));
        } else {
            $data = $hydratableInterface->extract($mapping['fields'] ?? []);
        }

        array_walk($data, function (&$value) use ($fields, $includes) {
            if (is_array($value)) {
                foreach ($value as $index => $item) {
                    if ($item instanceof HydratableInterface) {
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
                if (is_array($result)) {
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
