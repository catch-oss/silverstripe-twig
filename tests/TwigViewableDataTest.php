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
        $obj = TwigViewableData::create();
        $this->assertInstanceOf(ModelData::class, $obj);
    }

    public function testAbsoluteLinkBuildsCorrectUrl(): void
    {
        $obj = TwigViewableData::create();
        $result = $obj->AbsoluteLink('/test/path');
        $this->assertStringContainsString('/test/path', $result);
        $this->assertStringStartsWith('http', $result);
    }

    public function testAbsoluteLinkWithEmptyPath(): void
    {
        $obj = TwigViewableData::create();
        $result = $obj->AbsoluteLink('');
        $this->assertNotEmpty($result);
    }

    public function testCreateViaInjector(): void
    {
        $obj = TwigViewableData::create();
        $this->assertInstanceOf(TwigViewableData::class, $obj);
    }

    public function testHasTwigRendererTrait(): void
    {
        $obj = TwigViewableData::create();
        $this->assertTrue(method_exists($obj, 'renderTwig'));
        $this->assertTrue(method_exists($obj, 'buildTemplatesFromClassName'));
    }

    public function testDynamicPropertyAccess(): void
    {
        $obj = TwigViewableData::create();
        $obj->CustomProp = 'test';
        $this->assertSame('test', $obj->CustomProp);
    }
}
