<?php

namespace Spreadable\Template {

    use DOMDocument;
    use Exception;

    class Page
    {
        /**
         * @var string $_lang
         */
        private string $_lang;

        /**
         * @var string $_branding
         */
        private string $_branding;

        /**
         * @var Fragment $_head_fragment
         */
        private Fragment $_head_fragment;

        /**
         * @var Fragment $_body_fragment
         */
        private Fragment $_body_fragment;

        /**
         * @var string $_sep
         */
        private string $_sep;

        /**
         * @param string $lang
         * @param string $branding
         * @param Fragment $head_fragment
         * @param Fragment $body_fragment
         * @param string $sep
         * @throws Exception
         */
        public function __construct (
            string $lang,
            string $branding,
            Fragment $head_fragment,
            Fragment $body_fragment,
            string $sep = '-'
        )
        {
            $this->_branding = $branding;
            $this->_lang = $lang;
            $this->_head_fragment = $head_fragment;
            $this->_body_fragment = $body_fragment;
            $this->_sep = $sep;
        }

        /**
         * @return DOMDocument
         * @throws Exception
         */
        public function render (): DOMDocument
        {
            $document = clone Factory::document();

            $head_fragment = $this->_head_fragment->render()->getFragment();
            $head = $document->getElementsByTagName('head')[0];
            $head->appendChild($document->importNode($head_fragment, true));

            $body_fragment = $this->_body_fragment->render()->getFragment();
            $body = $document->getElementsByTagName('body')[0];
            $body->appendChild($document->importNode($body_fragment, true));

            $title = $head->getElementsByTagName('title')[0];
            $main = $body->getElementsByTagName('main')[0];
            $h1 = $main->getElementsByTagName('h1')[0];

            $html = $document->documentElement;
            $html->setAttribute('lang', $this->_lang);
            $title->textContent = "$h1->textContent $this->_sep $this->_branding";

            $document->formatOutput = true;
            $document->preserveWhiteSpace = false;

            return $document;
        }

        /**
         * @return string
         * @throws Exception
         */
        public function serialize (): string
        {
            return $this->render()->saveHTML();
        }
    }
}
