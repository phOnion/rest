<?php
declare(strict_types=1);

namespace Onion\REST\Factory;

use \Onion\Framework\Dependency\Interfaces\FactoryInterface;
use \Onion\REST\TransformerContainer;
use \Interop\Container\ContainerInterface;

class TransformerContainerFactory implements FactoryInterface
{
    public function build(ContainerInterface $container): TransformerContainer
    {
        assert(
            $container->has('rest'),
            new \RuntimeException('Missing configuration key "rest".')
        );

        $strategies = $container->get('rest');
        array_walk(
            $strategies,
            function (&$value) use ($container) {
                $value = $container->get($value);
            }
        );

        return new TransformerContainer($strategies);
    }
}
