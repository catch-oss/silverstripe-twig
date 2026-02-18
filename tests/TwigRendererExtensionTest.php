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
        $ext = new TwigRendererExtension();
        $this->assertInstanceOf(Extension::class, $ext);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(TwigRendererExtension::class));
    }
}
