<?php

namespace Spreadable\Template {

    use DOMDocument;
    use DOMDocumentFragment;
    use DOMXPath;
    use Exception;
    use Iterator;
    use WeakMap;
    use const E_USER_WARNING;
    use const LIBXML_HTML_NODEFDTD;
    use const LIBXML_HTML_NOIMPLIED;
    use const SORT_REGULAR;

    /**
     * Class Fragment
     * @package Lib\DOM
     */
    class Fragment
        implements Iterator
    {
        /**
         * @var DOMDocument[] $_documents
         */
        private static array $_documents;

        /**
         * @var int $_length
         */
        private int $_length;

        /**
         * @var array $_data
         */
        private array $_data = [];

        /**
         * @var DOMDocumentFragment $_fragment
         */
        private DOMDocumentFragment $_fragment;

        /**
         * @var string[] $_keys
         */
        private array $_keys;

        /**
         * @var Marker[] $_markers
         */
        private array $_markers;

        /**
         * @var string $_source
         */
        private string $_source;

        /**
         * @var int $_position
         */
        private int $_position = 0;

        /**
         * @var DOMXPath $_xPath
         */
        private DOMXPath $_xPath;

        /**
         * Fragment constructor.
         * @param string $source
         */
        public function __construct (
            string $source
        )
        {
            [$document, $loader] = self::getDocuments();
            $fragment = $document->createDocumentFragment();
            $slot = "<slot>$source</slot>";
            @$loader->loadHTML($slot, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
            $root = $loader->documentElement;
            $node = $document->importNode($root, true);
            $fragment->append(...$node->childNodes);
            $this->_fragment = $fragment;
            $this->_markers = Marker::parse($source);
            $this->_source = $source;
            $this->_xPath = new DOMXPath($document);
            $this->rewrite();
            $this->_keys = array_keys($this->_data);
            $this->_length = count($this->_keys);
        }

        public function __clone ()
        {
            $this->_data = array_merge_recursive($this->_data, []);
            $this->_fragment = $this->_fragment->cloneNode(true);
            $this->_position = 0;
        }

        /**
         * @param array $data = []
         * @return self
         */
        public function __invoke (
            array $data = []
        ) : self
        {
            $clone = clone $this;

            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->_data)) {
                    $clone->_data[$key] = $value;
                }
            }

            return $clone;
        }

        /**
         * @return DOMDocument[]
         */
        private static function getDocuments (): array
        {
            if (!isset(self::$_documents)) {
                $document = Factory::document();

                self::$_documents = [
                    $document,
                    clone $document
                ];
            }

            return self::$_documents;
        }

        /**
         * @return string
         */
        public function __toString (): string
        {
            return $this->_source;
        }

        /**
         * @return DOMDocumentFragment
         */
        public function getFragment (): DOMDocumentFragment
        {
            return $this->_fragment;
        }
        
        private function rewrite ()
        {
            $identifiers = new WeakMap();
            $nodes = [];

            foreach ($this->_markers as $marker) {
                $name = $marker->getName();
                $text = $marker->getText();
                $this->_data[$name] = null;
                $matches = $this->_xPath->query($text, $this->_fragment);

                foreach ($matches as $match) {
                    $nodes[] = $match;

                    if (!$identifiers->offsetExists($match)) {
                        $identifiers->offsetSet($match, []);
                    }

                    $identifiers->offsetSet($match, array_unique([
                        ...$identifiers->offsetGet($match),
                        $marker->getIdentifier()
                    ]));
                }
            }

            foreach (array_unique($nodes, SORT_REGULAR) as $node) {
                $parent = $node->parentNode;
                $document = $node->ownerDocument;
                $texts = [$node->nodeValue];

                foreach ($identifiers->offsetGet($node) as $match) {
                    foreach ([...$texts] as $key => $text) {
                        $exploded = explode($match, $text);
                        $last = count($exploded) - 1;
                        $merged = [];

                        foreach ($exploded as $pos => $value) {
                            $merged[] = $value;

                            if ($pos !== $last) {
                                $merged[] = $match;
                            }
                        }

                        array_splice($texts, $key, 1, $merged);
                    }
                }

                foreach ($texts as $text) {
                    $parent->insertBefore($document->createTextNode($text), $node);
                }

                $parent->removeChild($node);
            }
        }

        /**
         * @return self
         * @throws Exception
         */
        public function render (): self
        {
            $clone = clone $this;
            $data = $clone->_data;
            $fragment = $clone->_fragment;
            $markers = $clone->_markers;
            $xPath = $clone->_xPath;
            $matches = [];

            foreach ($markers as $marker) {
                $identifier = $marker->getIdentifier();
                $name = $marker->getName();
                $optional = $marker->isOptional();
                $attr = $marker->getAttr();
                $text = $marker->getText();
                $segments = $marker->getSegments();
                $attrs = $xPath->query($attr, $fragment);
                $texts = $xPath->query($text, $fragment);
                $matches[] = [$attrs, $texts, $identifier, $name, $optional, $segments];
            }

            foreach ($matches as $match) {
                [$attrs, $texts, $identifier, $name, $optional, $segments] = $match;
                $value = $data;

                foreach ($segments as $segment) {
                    if (!isset($value[$segment]) && !$optional) {
                        throw new Exception("Missing $name in $this->_source");
                    }

                    $value = $value[$segment] ?? null;
                }

                foreach ($attrs as $attr) {
                    $current = $attr->nodeValue;

                    if ($value === null && $current === $identifier) {
                        $attr->ownerElement->removeAttribute($attr->name);
                    } else {
                        $attr->nodeValue = implode($value, explode($identifier, $current));
                    }
                }

                foreach ($texts as $text) {
                    $document = $text->ownerDocument;
                    $parent = $text->parentNode;

                    if ($value instanceof self) {
                        $value = [$value];
                    }

                    if (is_array($value)) {
                        foreach ($value as $item) {
                            $node = $item instanceof self
                                ? $item->render()->_fragment
                                : $document->createTextNode($item);

                            $parent->insertBefore($node, $text);
                        }

                        $parent->removeChild($text);
                    } else {
                        $text->nodeValue = $value;
                    }
                }
            }

            return $clone;
        }

        /**
         * @return string
         * @throws Exception
         */
        public function serialize (): string
        {
            $fragment = $this->render()->_fragment;

            return $fragment->ownerDocument->saveHTML($fragment);
        }

        public function __get (
            string $key
        ): mixed
        {
            if (in_array($key, $this->_keys)) {
                trigger_error("Unknown key `$key`", E_USER_WARNING);
            }

            return $this->_data[$key];
        }

        public function rewind (): void
        {
            $this->_position = 0;
        }

        public function current (): array
        {
            $key = $this->_keys[$this->_position];

            return [
                $key => $this->_data[$key]
            ];
        }

        public function key (): int
        {
            return $this->_position;
        }

        public function next (): void
        {
            $this->_position += 1;
        }

        public function valid (): bool
        {
            return $this->_position < $this->_length;
        }
    }
}
