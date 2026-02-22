<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigControllerExtension;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;

class TwigControllerExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsExtension(): void
    {
        // GIVEN the TwigControllerExtension class
        // WHEN we instantiate it
        $ext = new TwigControllerExtension();

        // THEN it should extend SS6's Extension base class (renamed from DataExtension)
        $this->assertInstanceOf(Extension::class, $ext);
    }

    public function testClassExists(): void
    {
        // GIVEN the module is loaded
        // WHEN we check for the TwigControllerExtension class
        // THEN it should be autoloadable
        $this->assertTrue(class_exists(TwigControllerExtension::class));
    }
}
