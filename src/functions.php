<?php

namespace Spreadable\Template {

    use DOMDocument;
    use Exception;

    /**
     * @param string $file
     * @return Fragment
     */
    function fragment (
        string $file
    ): Fragment
    {
        return Factory::fragment($file);
    }

    /**
     * @param string $lang
     * @param string $branding
     * @param Fragment $head_fragment
     * @param Fragment $body_fragment
     * @return Page
     * @throws Exception
     */
    function page (
        string $lang,
        string $branding,
        Fragment $head_fragment,
        Fragment $body_fragment
    ): Page
    {
        return Factory::page($lang, $branding, $head_fragment, $body_fragment);
    }

}