<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigViewableData;
use SilverStripe\Control\Director;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ModelData;

class TwigViewableDataTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        Director::config()->set('alternate_base_url', 'http://localhost/');
    }

    public function testExtendsModelData(): void
    {
        // GIVEN the TwigViewableData class (which should extend ModelData in SS6)
        // WHEN we create an instance
        $obj = TwigViewableData::create();

        // THEN it should be an instance of ModelData (renamed from ViewableData in SS6)
        $this->assertInstanceOf(ModelData::class, $obj);
    }

    public function testAbsoluteLinkBuildsCorrectUrl(): void
    {
        // GIVEN a TwigViewableData instance with alternate_base_url configured
        $obj = TwigViewableData::create();

        // WHEN we call AbsoluteLink with a relative path
        $result = $obj->AbsoluteLink('/test/path');

        // THEN it should return a full URL containing the path
        $this->assertStringContainsString('/test/path', $result);
        $this->assertStringStartsWith('http', $result);
    }

    public function testAbsoluteLinkWithEmptyPath(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we call AbsoluteLink with an empty string
        $result = $obj->AbsoluteLink('');

        // THEN it should still return a base URL (not empty or error)
        $this->assertNotEmpty($result);
    }

    public function testCreateViaInjector(): void
    {
        // GIVEN the Injector is configured (via SapphireTest)
        // WHEN we create a TwigViewableData via the Injector-aware ::create()
        $obj = TwigViewableData::create();

        // THEN it should return a valid TwigViewableData instance
        $this->assertInstanceOf(TwigViewableData::class, $obj);
    }

    public function testHasTwigRendererTrait(): void
    {
        // GIVEN a TwigViewableData instance (which uses the TwigRenderer trait)
        $obj = TwigViewableData::create();

        // WHEN we check for trait-provided methods
        // THEN renderTwig and buildTemplatesFromClassName should exist
        $this->assertTrue(method_exists($obj, 'renderTwig'));
        $this->assertTrue(method_exists($obj, 'buildTemplatesFromClassName'));
    }

    public function testDynamicPropertyAccess(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we set a dynamic property
        $obj->CustomProp = 'test';

        // THEN it should be readable back
        $this->assertSame('test', $obj->CustomProp);
    }
}
