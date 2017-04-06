<?php

namespace Tests;

use Onion\Framework\Hydrator\Interfaces\HydratableInterface;
use Onion\Framework\Hydrator\PropertyHydrator;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Interfaces\SerializableInterface;
use \Onion\Framework\Rest\Transformer;
use Psr\Link\LinkInterface;
use \Tests\Stubs\Serializer\A;

class TransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Transformer
     */
    private $testable;

    public function setUp()
    {
        $this->testable = new Transformer([
            A::class => [
                'rel' => 'a',
                'links' => [
                    ['rel' => 'self', 'href' => '/entity/id'],
                ],
                'fields' => ['id']
            ]
        ]);
    }

    public function testSimpleSerialization()
    {
        $this->assertInstanceOf(EntityInterface::class,$this->testable->transform(new A()));
        $entity = $this->testable->transform(new A());
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
    }

    public function testExceptionWhenNoMappingDefined()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->testable->transform(new class implements HydratableInterface {
            use PropertyHydrator;
        });
    }

    public function testSelfDefiningObjects()
    {
        $entity = new class implements SerializableInterface {
            use PropertyHydrator;

            public $id = 5;

            public function getMappings(): array
            {
                return [
                    'rel' => 'a',
                    'links' => [
                        ['rel' => 'self', 'href' => '/entity/id', 'name' => 'test'],
                    ],
                    'fields' => ['id']
                ];
            }
        };

        $this->assertInstanceOf(EntityInterface::class,$this->testable->transform($entity));
        $entity = $this->testable->transform($entity);
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
    }

    public function testObjectWithRelations()
    {
        $entity = new class implements SerializableInterface {
            use PropertyHydrator;

            public $id = 5;

            public function stubs(): array
            {
                return [
                    new class implements SerializableInterface {
                        use PropertyHydrator;

                        public $name = 'Smith';

                        public function getMappings(): array
                        {
                            return [
                                'rel' => 'b',
                                'fields' => ['name'],
                                'links' => [
                                    ['rel' => 'self', 'href' => '/']
                                ]
                            ];
                        }
                    }
                ];
            }

            public function getMappings(): array
            {
                return [
                    'rel' => 'a',
                    'links' => [
                        ['rel' => 'self', 'href' => '/entity/id', 'name' => 'test'],
                    ],
                    'fields' => ['id'],
                    'relations' => [
                        'children' => 'stubs'
                    ]
                ];
            }
        };

        $this->assertInstanceOf(EntityInterface::class,$this->testable->transform($entity));
        $entity = $this->testable->transform($entity);
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
        $this->assertArrayHasKey('children', $entity->getEmbedded());
        $this->assertContainsOnlyInstancesOf(EntityInterface::class, $entity->getEmbedded()['children']);
    }

    public function testObjectWithRelationsReturningHydratable()
    {
        $entity = new class implements SerializableInterface {
            use PropertyHydrator;

            public $id = 5;

            public function stub(): HydratableInterface
            {
                return new class implements SerializableInterface {
                    use PropertyHydrator;

                    public $name = 'Smith';

                    public function getMappings(): array
                    {
                        return [
                            'rel' => 'b',
                            'fields' => ['name'],
                            'links' => [
                                ['rel' => 'self', 'href' => '/']
                            ]
                        ];
                    }
                };
            }

            public function getMappings(): array
            {
                return [
                    'rel' => 'a',
                    'links' => [
                        ['rel' => 'self', 'href' => '/entity/id', 'name' => 'test'],
                    ],
                    'fields' => ['id'],
                    'relations' => [
                        'child' => 'stub'
                    ]
                ];
            }
        };

        $this->assertInstanceOf(EntityInterface::class,$this->testable->transform($entity));
        $entity = $this->testable->transform($entity);
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
        $this->assertArrayHasKey('child', $entity->getEmbedded());
        $this->assertInstanceOf(EntityInterface::class, $entity->getEmbedded()['child']);
    }

    public function testObjectWithPropertyValueImplementingHydratable()
    {
        $entity = new class implements SerializableInterface {
            use PropertyHydrator;

            public $id = 5;

            public $stubs;

            public function __construct()
            {
                $this->stubs = [
                    new class implements SerializableInterface {
                        use PropertyHydrator;

                        public $name = 'Smith';

                        public function getMappings(): array
                        {
                            return [
                                'rel' => 'b',
                                'fields' => ['name'],
                                'links' => [
                                    ['rel' => 'self', 'href' => '/']
                                ]
                            ];
                        }
                    }
                ];
            }

            public function getMappings(): array
            {
                return [
                    'rel' => 'a',
                    'links' => [
                        ['rel' => 'self', 'href' => '/entity/id', 'name' => 'test'],
                    ],
                    'fields' => ['id', 'stubs'],
                ];
            }
        };

        $this->assertInstanceOf(EntityInterface::class,$this->testable->transform($entity));
        $entity = $this->testable->transform($entity);
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
        $this->assertArrayHasKey('stubs', $entity->getEmbedded());
        $this->assertNotContains('stubs', $entity->getData());
        $this->assertTrue(is_array($entity->getEmbedded()['stubs']));
        $this->assertContainsOnlyInstancesOf(EntityInterface::class, $entity->getEmbedded()['stubs']);
    }

    public function testObjectWithPropertyValuesImplementingHydratable()
    {
        $entity = new class implements SerializableInterface {
            use PropertyHydrator;

            public $id = 5;

            public $stubs;

            public function __construct()
            {
                $this->stubs = [
                    new class implements SerializableInterface {
                        use PropertyHydrator;

                        public $name = 'Smith';

                        public function getMappings(): array
                        {
                            return [
                                'rel' => 'b',
                                'fields' => ['name'],
                                'links' => [
                                    ['rel' => 'self', 'href' => '/']
                                ]
                            ];
                        }
                    },
                    new class implements SerializableInterface {
                        use PropertyHydrator;

                        public $name = 'Smith';

                        public function getMappings(): array
                        {
                            return [
                                'rel' => 'b',
                                'fields' => ['name'],
                                'links' => [
                                    ['rel' => 'self', 'href' => '/']
                                ]
                            ];
                        }
                    }
                ];
            }

            public function getMappings(): array
            {
                return [
                    'rel' => 'a',
                    'links' => [
                        ['rel' => 'self', 'href' => '/entity/id', 'name' => 'test'],
                    ],
                    'fields' => ['id', 'stubs'],
                ];
            }
        };

        $this->assertInstanceOf(EntityInterface::class, $this->testable->transform($entity));
        $entity = $this->testable->transform($entity);
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
        $this->assertArrayHasKey('stubs', $entity->getEmbedded());
        $this->assertNotContains('stubs', $entity->getData());
        $this->assertTrue(is_array($entity->getEmbedded()['stubs']));
        $this->assertContainsOnlyInstancesOf(EntityInterface::class, $entity->getEmbedded()['stubs']);
        $this->assertCount(2, $entity->getEmbedded()['stubs']);
    }

    public function testObjectWithPropertyValueIsHydratable()
    {
        $entity = new class implements SerializableInterface {
            use PropertyHydrator;

            public $id = 5;

            public $stub;

            public function __construct()
            {
                $this->stub = new class implements SerializableInterface {
                    use PropertyHydrator;

                    public $name = 'Smith';

                    public function getMappings(): array
                    {
                        return [
                            'rel' => 'b',
                            'fields' => ['name'],
                            'links' => [
                                ['rel' => 'self', 'href' => '/']
                            ]
                        ];
                    }
                };
            }

            public function getMappings(): array
            {
                return [
                    'rel' => 'a',
                    'links' => [
                        ['rel' => 'self', 'href' => '/entity/id', 'name' => 'test'],
                    ],
                    'fields' => ['id', 'stub'],
                ];
            }
        };

        $this->assertInstanceOf(EntityInterface::class, $this->testable->transform($entity));
        $entity = $this->testable->transform($entity);
        $this->assertSame('a', $entity->getRel());
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinksByRel('self'));
        $this->assertContainsOnlyInstancesOf(LinkInterface::class, $entity->getLinks());
        $this->assertArrayHasKey('id', $entity->getData());
        $this->assertArrayHasKey('stub', $entity->getEmbedded());
        $this->assertNotContains('stub', $entity->getData());
        $this->assertInstanceOf(EntityInterface::class, $entity->getEmbedded()['stub']);
    }

}
