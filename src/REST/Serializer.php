<?php
declare(strict_types=1);

namespace Onion\REST;

use \Onion\Framework\Hydrator\Interfaces\HydratableInterface as Hydrator;

class Serializer
{
    /**
     * @var array
     */
    private $mappings;

    /**
     * Creates the serializer with all meta mappings
     * that provide the necessary information in order
     * to manipulate the the object beforehand automatically
     *
     * @param array $mapping A list of metadata mappings to use for serialization
     */
    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @param Hydrator $hydrator A hyratable object to transform
     */
    public function serialize(Hydrator $hydrator)
    {
        $serialized = [];
        $mapping = [];

        if (isset($this->mappings[get_class($hydrator)])) {
            $mapping =
                $this->mappings[get_class($hydrator)];
        }

        if (isset($mapping['fields'])) {
            $data = $hydrator->extract($mapping['fields']);
        }

        if (!isset($serialized['entity'])) {
            $data = $hydrator->extract();
        }
        
        if (isset($mapping['links'])) {
            foreach ($mapping['links'] as $rel => $link) {
                if (isset($link['href'])) {
                    $link['href'] = str_replace(
                        array_map(
                            function ($value) {
                                return "{{$value}}";
                            },
                            array_keys($data)
                        ),
                        array_values($data),
                        $link['href']
                    );
                }

                $serialized['_links'][$rel] = $link;
            }
        }

        $serialized = array_merge($serialized, $data);

        if (isset($mapping['relations'])) {
            $serialized['_embedded'] = [];

            foreach ($mapping['relations'] as $name => $method) {
                $embedded = $hydrator->{$method}();

                if (is_array($embedded)) {
                    $embedded = array_map(
                        function ($value) {
                            return $this->serialize($value);
                        },
                        $embedded
                    );
                }

                if (is_object($embedded)) {
                    $embedded = $this->serialized($embedded);
                }

                $serialized['_embedded'][$name] = $embedded;
            }
        }

        return $serialized;
    }
}
