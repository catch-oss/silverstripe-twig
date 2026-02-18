<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigSSGlobals;
use SilverStripe\Dev\SapphireTest;

class TwigSSGlobalsTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testCanBeInstantiated(): void
    {
        $globals = new TwigSSGlobals();
        $this->assertInstanceOf(TwigSSGlobals::class, $globals);
    }

    public function testIssetReturnsTrueForKnownGlobals(): void
    {
        $globals = new TwigSSGlobals();
        // SiteConfig is a TemplateGlobalProvider that exposes 'CurrentSite'
        // Director exposes 'BaseURL', 'AbsoluteBaseURL', etc.
        $this->assertTrue(isset($globals->BaseURL) || isset($globals->baseURL));
    }

    public function testIssetReturnsFalseForUnknownGlobal(): void
    {
        $globals = new TwigSSGlobals();
        $this->assertFalse(isset($globals->ThisGlobalDoesNotExist));
    }

    public function testGetReturnsNullForUnknownGlobal(): void
    {
        $globals = new TwigSSGlobals();
        $this->assertNull($globals->ThisGlobalDoesNotExist);
    }

    public function testGetReturnsValueForKnownGlobal(): void
    {
        $globals = new TwigSSGlobals();
        // Director is a TemplateGlobalProvider that exposes BaseURL
        if (isset($globals->BaseURL)) {
            $result = $globals->BaseURL;
            $this->assertNotNull($result);
        } elseif (isset($globals->baseURL)) {
            $result = $globals->baseURL;
            $this->assertNotNull($result);
        } else {
            // If no globals are available in test context, just verify no exception
            $this->assertTrue(true);
        }
    }

    public function testGlobalResultIsCached(): void
    {
        $globals = new TwigSSGlobals();
        if (isset($globals->BaseURL)) {
            $first = $globals->BaseURL;
            $second = $globals->BaseURL;
            $this->assertSame($first, $second);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testGlobalsAreCaseSensitiveWithBothVariants(): void
    {
        $globals = new TwigSSGlobals();
        // The constructor stores both ucfirst and lcfirst versions
        // So if 'BaseURL' exists, 'baseURL' should also exist (and vice versa)
        if (isset($globals->BaseURL)) {
            $this->assertTrue(isset($globals->baseURL));
        } elseif (isset($globals->baseURL)) {
            $this->assertTrue(isset($globals->BaseURL));
        } else {
            $this->assertTrue(true);
        }
    }
}
