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
        $ext = new TwigControllerExtension();
        $this->assertInstanceOf(Extension::class, $ext);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(TwigControllerExtension::class));
    }
}
