<?php
declare(strict_types=1);

namespace Onion\REST\Transformers;

class XmlHalTransformer implements Interfaces\StrategyInterface
{
    public function transform(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resource></resource>');
        if (isset($data['_links']['self'])) {
                $xml->addAttribute('href', $data['_links']['self']['href']);
                unset($data['_links']['self']);
        }
        if (isset($data['$schema'])) {
                $xml->addAttribute('xmlns:xmlns', $data['$schema']);
                unset($data['$schema']);
        }
            return $this->formatResource($xml, $data);
    }

    public function formatResource(\SimpleXMLElement $xml, $data): string
    {
        foreach ($data as $key => $value) {
            if ($key === '$schema') {
                        continue;
            }
            
            if ($key === '_links') {
                foreach ($value as $rel => $href) {
                    if ($rel === 'curies') {
                        foreach ($href as $attrs) {
                                $xml->addAttribute('xmlns:xmlns:' . $attrs['name'], $attrs['href']);
                        }
                        continue;
                    }
                                                                                                
                    if (count($href) === count($href, COUNT_RECURSIVE)) {
                        $link = $xml->addChild('link');
                        $link->addAttribute('rel', $rel);
                        if (is_array($href)) {
                            foreach ($href as $attr => $attrs) {
                                if (!is_array($attrs)) {
                                    array_walk(
                                        $href,
                                        function ($attr, $name) use ($link) {
                                                $link->addAttribute($name, (string) $attr);
                                        }
                                    );
                                    break;
                                }
                            }
                        }
                    }

                    if (count($href) !== count($href, COUNT_RECURSIVE)) {
                        foreach ($href as $index => $hr) {
                            $link = $xml->addChild('link');
                            $link->addAttribute('rel', $rel);
                            if (is_array($hr)) {
                                foreach ($hr as $attr => $attrs) {
                                    if (!is_array($attrs)) {
                                        array_walk(
                                            $hr,
                                            function ($attr, $name) use ($link) {
                                                    $link->addAttribute($name, (string) $attr);
                                            }
                                        );
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                continue;
            }

            if ($key === '_embedded') {
                foreach ($value as $relation => $embedded) {
                    foreach ($embedded as $embed) {
                        $container = $xml->addChild('resource');
                        $container->addAttribute('rel', $relation);
                        $container->addAttribute('href', $embed['_links']['self']['href']);
                        unset($embed['_links']['self']);
                        $this->formatResource($container, $embed);
                    }
                }
                continue;
            }

            if ($key === '_meta') {
                $container = $xml->addChild('meta');
                $this->formatResource($container, $value);
                continue;
            }

            if (!is_string($key)) {
                $this->formatResource($xml, $value);
                continue;
            }

            $child = $xml->addChild($key);
            // Do not force clients to pre-process elements
            if ($child !== null) {
                $child_node = dom_import_simplexml($child);
                $child_owner = $child_node->ownerDocument;
                $child_node->appendChild($child_owner->createCDATASection((string) $value));
            }
        }
            
        return $xml->asXML();
    }
}
