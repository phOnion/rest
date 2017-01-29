<?php
declare(strict_types=1);

namespace Onion\REST;

use \Onion\Framework\Http\Header\Accept;
use \Onion\REST\Transformers\Interfaces\TransformerInterface;

class TransformerContainer
{
    /**
     * @var TransformerInterface
     */
    private $strategies = [];

    /**
     * @param TransformerInterface[] $strategies
     */
    public function __construct(array $transformers)
    {
        $this->strategies = $stransformers;
    }

    public function getDefaultTransformer(): TransformerInterface
    {
        return array_shift$this->strategies);
    }

    public function negotiateTransformer(Accept $accept): TransformerInterface
    {
        $list = [];
        foreach ($this->strategies as $type => $strategy) {
            if ($header->supports($type)) {
                $list[$header->getPriority($type)] = $strategy;
            }
        }

        if ($list === []) {
            return $this->getDefaultTransformer();
        }

        return array_pop($list);
    }
}
