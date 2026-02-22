<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigRendererExtension;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;

class TwigRendererExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsExtension(): void
    {
        // GIVEN the TwigRendererExtension class
        // WHEN we instantiate it
        $ext = new TwigRendererExtension();

        // THEN it should extend SS6's Extension base class (renamed from DataExtension)
        $this->assertInstanceOf(Extension::class, $ext);
    }

    public function testClassExists(): void
    {
        // GIVEN the module is loaded
        // WHEN we check for the TwigRendererExtension class
        // THEN it should be autoloadable
        $this->assertTrue(class_exists(TwigRendererExtension::class));
    }
}
