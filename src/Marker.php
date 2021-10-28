<?php

namespace Spreadable\Template {

    use const PREG_SET_ORDER;

    const IDENTIFIER_PATTERN = '/{((\??)([a-z]\w*(?:\.[a-z]\w*)*))}/m';

    /**
     * Class Marker
     * @package Spreadable\Template
     */
    class Marker
    {
        /**
         * @var string $_identifier
         */
        private string $_identifier;

        /**
         * @var bool $_optional
         */
        private bool $_optional;

        /**
         * @var string $_name
         */
        private string $_name;

        /**
         * @var string $_attr
         */
        private string $_attr;

        /**
         * @var string $_text
         */
        private string $_text;

        /**
         * @var string[] $_segments
         */
        private array $_segments;

        /**
         * Marker constructor.
         * @param string $identifier
         * @param string $name
         * @param bool $optional
         */
        public function __construct(
            string $identifier,
            string $name,
            bool $optional
        )
        {
            $this->_identifier = $identifier;
            $this->_optional = $optional;
            $this->_name = $name;
            $this->_segments = explode('.', $name);
            $this->_attr = "./*//attribute::*[contains(., '{$identifier}')]";
            $this->_text = "(./*//text()|./text())[contains(., '{$identifier}')]";
        }

        /**
         * @param string $source
         * @return Marker[]
         */
        public static function parse (
            string $source
        ): array
        {
            $matches = [];
            $markers = [];
            preg_match_all(IDENTIFIER_PATTERN, $source, $matches, PREG_SET_ORDER);

            foreach ($matches as [$identifier, , $optional, $name]) {
                $markers[$name] = new self($identifier, $name, (bool) $optional);
            }

            return $markers;
        }

        /**
         * @return string
         */
        public function getIdentifier(): string
        {
            return $this->_identifier;
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->_name;
        }

        /**
         * @return string
         */
        public function getAttr(): string
        {
            return $this->_attr;
        }

        /**
         * @return string
         */
        public function getText(): string
        {
            return $this->_text;
        }

        /**
         * @return string[]
         */
        public function getSegments(): array
        {
            return $this->_segments;
        }

        /**
         * @return bool
         */
        public function isOptional(): bool
        {
            return $this->_optional;
        }
    }
}
