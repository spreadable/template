<?php

namespace Spreadable\Template {
    use DOMDocument;
    use Exception;

    const TEMPLATE = '<!DOCTYPE html>
<html lang="en">
  <head>
    <title></title>
    <meta charset="utf-8" />
  </head>
  <body></body>
</html>';

    abstract class Factory {
        /**
         * @var DOMDocument $_document
         */
        static private DOMDocument $_document;

        /**
         * @var array $_sources
         */
        static private array $_sources = [];

        /**
         * @var array $_fragments
         */
        static private array $_fragments = [];

        final private function __construct ()
        {}

        /**
         * @return DOMDocument
         */
        public static function document (): DOMDocument
        {
            if (!isset(self::$_document)) {
                self::$_document = new DOMDocument('1.0', 'utf-8');
                @self::$_document->loadHTML(TEMPLATE);
            }

            return self::$_document;
        }

        /**
         * @param string $file
         * @param iterable $data = []
         * @return Fragment
         */
        public static function fragment (
            string $file,
            iterable $data = []
        ): Fragment
        {
            if (!isset(self::$_sources[$file])) {
                self::$_sources[$file] = file_get_contents($file);
            }

            return self::parse(self::$_sources[$file], $data);
        }

        /**
         * @param string $source
         * @param iterable $data = []
         * @return Fragment
         */
        public static function parse (
            string $source,
            iterable $data = []
        ): Fragment
        {
            if (!isset(self::$_fragments[$source])) {
                self::$_fragments[$source] = new Fragment($source);
            }

            $fragment = self::$_fragments[$source];

            return $fragment($data);
        }

        /**
         * @param string $lang
         * @param string $branding
         * @param Fragment $head_fragment
         * @param Fragment $body_fragment
         * @param string $sep
         * @return Page
         * @throws Exception
         */
        public static function page (
            string $lang,
            string $branding,
            Fragment $head_fragment,
            Fragment $body_fragment,
            string $sep = '-'
        ): Page
        {
            return new Page($lang, $branding, $head_fragment, $body_fragment, $sep);
        }
    }
}
