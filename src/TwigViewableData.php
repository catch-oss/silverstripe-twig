<?php

namespace Azt3k\SS\Twig;

use SilverStripe\Control\Director;
use SilverStripe\Model\ModelData;

class TwigViewableData extends ModelData
{
    use TwigRenderer;

    public function AbsoluteLink($path)
    {
        return trim(Director::absoluteBaseURL(), '/') . $path;
    }
}
