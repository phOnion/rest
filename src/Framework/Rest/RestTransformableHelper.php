<?php declare(strict_types = 1);
namespace Onion\Framework\Rest;

use Fig\Link\Link;
use Onion\Framework\Rest\Interfaces\EntityInterface;
use Onion\Framework\Rest\Interfaces\TransformableInterface;

trait RestTransformableHelper
{
    public function handleLinks(EntityInterface $entity, array $links, array $data = []): EntityInterface
    {
        $placeholders = array_keys($data);
        $replacements = array_values($data);

        foreach ($links as $link) {
            /**
             * @var $link array[]
             */
            $lnk = new Link($link['rel'], str_replace(
                array_map(function ($value) {
                    return "{{$value}}";
                }, $placeholders),
                $replacements,
                $link['href']
            ));
            foreach ($link as $attr => $value) {
                if ($attr !== 'rel' && $attr !== 'href') {
                    $lnk = $lnk->withAttribute($attr, $value);
                }
            }

            $entity = $entity->withLink($lnk);
        }

        return $entity;
    }

    public function walkEmbeddedEntities(EntityInterface $entity, array $embedded): EntityInterface
    {
        foreach ($embedded as $rel => $resource) {
            if (is_array($resource)) {
                /**
                 * @var $resource TransformableInterface[]
                 */
                foreach ($resource as $item) {
                    $entity = $entity->addEmbedded($rel, $item->transform());
                }
            }

            if ($resource instanceof TransformableInterface) {
                $entity = $entity->addEmbedded($rel, $resource->transform(), false);
            }
        }

        return $entity;
    }
}
