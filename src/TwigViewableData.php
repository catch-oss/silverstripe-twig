<?php

namespace Azt3k\SS\Twig;

use IOD\Util\Debug;
use SilverStripe\Control\Director;
use SilverStripe\View\ViewableData;

class TwigViewableData extends ViewableData
{
    use TwigRenderer;
    // public function __construct($object = null)
    // {
    //     $thi

    //     if (\is_iterable($object)) {
    //         foreach ($object as $k => $v) {
    //             Debug::d($k, 'k', false);
    //             $this->{$k} = $v;
    //         };
    //     }
    // }

    public function AbsoluteLink($path)
    {
        return trim(Director::absoluteBaseURL(), '/') . $path;
    }
}
