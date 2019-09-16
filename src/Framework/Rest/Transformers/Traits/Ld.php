<?php
namespace Onion\Framework\Rest\Transformers\Traits;

use Onion\Framework\Rest\Transformers\LdTransformer;

trait Ld
{
    private $transformer;

    private function getTransformer()
    {
        if (!$this->transformer) {
            $this->transformer = new LdTransformer();
        }

        return $this->transformer;
    }
}
