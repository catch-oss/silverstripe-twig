<?php

namespace Azt3k\SS\Twig;

use IOD\Util\Debug;
use SilverStripe\Control\Director;
use SilverStripe\View\ViewableData;

class TwigViewableData extends ViewableData
{
    use TwigRenderer;

    public function AbsoluteLink($path)
    {
        return trim(Director::absoluteBaseURL(), '/') . $path;
    }
}
