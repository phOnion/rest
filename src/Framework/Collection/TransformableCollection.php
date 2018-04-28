<?php declare(strict_types=1);
namespace Onion\Framework\Collection;

use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Interfaces\TransformableInterface;
use Onion\Framework\Rest\Entity;

class TransformableCollection implements \Iterator, \Countable, TransformableInterface
{
    /** @var TransformableInterface[] */
    private $items;
    private $links = [];
    private $rel;

    public function __construct(string $rel, iterable $items, iterable $links = [])
    {
        $this->rel = $rel;
        if (is_array($items)) {
            $items = new \ArrayIterator($items);
        }

        $this->items = $items;
        $this->links = $links;
    }

    public function transform(array $includes = [], array $fields = []): EntityInterface
    {
        $this->rewind();
        /** @var EntityInterface $first */
        $first = $this->current()->transform($fields);
        $entity = new Entity('collection');
        foreach ($this->items as $item) {
            /**
             * @var TransformableInterface $item
             * @var Entity $embedded
             **/
            $embedded = $item->transform($includes, $fields);
            if (!empty($includes) && !in_array($embedded)) {
                continue;
            }

            $entity = $entity->withAddedEmbedded($embedded->getRel(), $embedded);
        }

        foreach ($this->links as $link) {
            $entity = $entity->withLink($link);
        }

        return $entity->withData([
            'count' => count($this->items),
        ])->withL;
    }

    public function next()
    {
        $this->items->next();
    }

    public function current()
    {
        return $this->items->current();
    }

    public function valid()
    {
        return $this->items->valid();
    }

    public function rewind()
    {
        $this->items->rewind();
    }

    public function key()
    {
        return $this->items->key();
    }

    public function count()
    {
        return count($this->items);
    }
}