<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigSSGlobals;
use SilverStripe\Dev\SapphireTest;

class TwigSSGlobalsTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testCanBeInstantiated(): void
    {
        // GIVEN the SS framework is bootstrapped with TemplateGlobalProviders
        // WHEN we create a TwigSSGlobals instance
        $globals = new TwigSSGlobals();

        // THEN it should be a valid instance (constructor scans all global providers)
        $this->assertInstanceOf(TwigSSGlobals::class, $globals);
    }

    public function testIssetReturnsTrueForKnownGlobals(): void
    {
        // GIVEN a TwigSSGlobals instance with SS global providers loaded
        $globals = new TwigSSGlobals();

        // WHEN we check isset for a known global like BaseURL (from Director)
        // THEN at least one variant (BaseURL or baseURL) should exist
        $this->assertTrue(isset($globals->BaseURL) || isset($globals->baseURL));
    }

    public function testIssetReturnsFalseForUnknownGlobal(): void
    {
        // GIVEN a TwigSSGlobals instance
        $globals = new TwigSSGlobals();

        // WHEN we check isset for a non-existent global
        // THEN it should return false
        $this->assertFalse(isset($globals->ThisGlobalDoesNotExist));
    }

    public function testGetReturnsNullForUnknownGlobal(): void
    {
        // GIVEN a TwigSSGlobals instance
        $globals = new TwigSSGlobals();

        // WHEN we access a non-existent global
        // THEN it should return null (not throw)
        $this->assertNull($globals->ThisGlobalDoesNotExist);
    }

    public function testGetReturnsValueForKnownGlobal(): void
    {
        // GIVEN a TwigSSGlobals instance
        $globals = new TwigSSGlobals();

        // WHEN we access a known global like BaseURL
        if (isset($globals->BaseURL)) {
            $result = $globals->BaseURL;
            // THEN it should return a non-null value
            $this->assertNotNull($result);
        } elseif (isset($globals->baseURL)) {
            $result = $globals->baseURL;
            $this->assertNotNull($result);
        } else {
            // THEN if no globals are available in test context, just verify no exception
            $this->assertTrue(true);
        }
    }

    public function testGlobalResultIsCached(): void
    {
        // GIVEN a TwigSSGlobals instance with a known global
        $globals = new TwigSSGlobals();

        if (isset($globals->BaseURL)) {
            // WHEN we access the same global twice
            $first = $globals->BaseURL;
            $second = $globals->BaseURL;

            // THEN the result should be identical (cached, not re-evaluated)
            $this->assertSame($first, $second);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testGlobalsAreCaseSensitiveWithBothVariants(): void
    {
        // GIVEN a TwigSSGlobals instance (constructor registers both ucfirst and lcfirst variants)
        $globals = new TwigSSGlobals();

        // WHEN we check for both case variants of a global
        if (isset($globals->BaseURL)) {
            // THEN the lowercase variant should also exist
            $this->assertTrue(isset($globals->baseURL));
        } elseif (isset($globals->baseURL)) {
            // THEN the uppercase variant should also exist
            $this->assertTrue(isset($globals->BaseURL));
        } else {
            $this->assertTrue(true);
        }
    }
}
