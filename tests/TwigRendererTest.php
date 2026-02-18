<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigContainer;
use Azt3k\SS\Twig\TwigViewableData;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBHTMLText;

class TwigRendererTest extends SapphireTest
{
    protected $usesDatabase = false;

    private array $originalConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConfig = TwigContainer::getConfig();

        // Add the test fixtures directory to template paths
        TwigContainer::extendConfig([
            'twig.module_template_paths' => [
                dirname(__DIR__) . '/tests/fixtures',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        TwigContainer::setConfig($this->originalConfig);
        parent::tearDown();
    }

    public function testBuildTemplatesFromClassNameReturnsArray(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class);
        $this->assertIsArray($templates);
        $this->assertNotEmpty($templates);
    }

    public function testBuildTemplatesFromClassNameIncludesClassName(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class);
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData', $templates);
    }

    public function testBuildTemplatesFromClassNameIncludesParentClasses(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class);
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData', $templates);
        $this->assertContains('SilverStripe/Model/ModelData', $templates);
    }

    public function testBuildTemplatesFromClassNameWithAction(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class, 'show');
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData_show', $templates);
    }

    public function testBuildTemplatesFromClassNameIndexActionIgnored(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class, 'index');
        $this->assertNotContains('Azt3k/SS/Twig/TwigViewableData_index', $templates);
    }

    public function testBuildTemplatesFromClassNameStopsAtController(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(Controller::class);
        $this->assertEmpty($templates);
    }

    public function testBuildTemplatesFromClassNameWithNullAction(): void
    {
        $obj = TwigViewableData::create();
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class, null);
        $this->assertIsArray($templates);
    }

    public function testCustomiseWithArraySetsProperties(): void
    {
        $obj = TwigViewableData::create();
        $result = $obj->customise(['TestProp' => 'value']);
        $this->assertSame($obj, $result);
        $this->assertSame('value', $obj->TestProp);
    }

    public function testCustomiseReturnsSelf(): void
    {
        $obj = TwigViewableData::create();
        $result = $obj->customise(['key' => 'val']);
        $this->assertInstanceOf(TwigViewableData::class, $result);
    }

    public function testDicPropertyCreatesTwigContainer(): void
    {
        $obj = TwigViewableData::create();
        $dic = $obj->dic;
        $this->assertInstanceOf(TwigContainer::class, $dic);
    }

    public function testIncludeRequirementsDefaultsToTrue(): void
    {
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionProperty($obj, 'includeRequirements');
        $this->assertTrue($reflection->getValue($obj));
    }

    public function testRenderTwigWithTemplate(): void
    {
        $obj = TwigViewableData::create();
        $obj->Title = 'World';

        // Render using the test template
        $reflection = new \ReflectionMethod($obj, 'renderTwig');
        $result = $reflection->invoke($obj, ['test'], $obj);
        $this->assertStringContainsString('Hello World', $result);
    }

    public function testRenderTwigThrowsForMissingTemplate(): void
    {
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'renderTwig');

        $this->expectException(\InvalidArgumentException::class);
        $reflection->invoke($obj, ['nonexistent_template_xyz'], $obj);
    }

    public function testGetTwigTemplateReturnsTemplateWrapper(): void
    {
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTwigTemplate');
        $template = $reflection->invoke($obj, ['test']);
        $this->assertInstanceOf(\Twig\TemplateWrapper::class, $template);
    }

    public function testGetTwigTemplateThrowsForEmptyArray(): void
    {
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTwigTemplate');

        $this->expectException(\InvalidArgumentException::class);
        $reflection->invoke($obj, []);
    }

    public function testGetTwigTemplateHandlesNestedArrayTemplates(): void
    {
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTwigTemplate');
        // Simulate the nested array format: [['type' => 'Includes', 0 => 'test']]
        $template = $reflection->invoke($obj, [['type' => 'Includes', 0 => 'test']]);
        $this->assertInstanceOf(\Twig\TemplateWrapper::class, $template);
    }

    public function testRenderWithReturnDBHTMLText(): void
    {
        $obj = TwigViewableData::create();
        $obj->Title = 'Test';
        $result = $obj->renderWith(['test']);
        $this->assertInstanceOf(DBHTMLText::class, $result);
        $this->assertStringContainsString('Hello Test', (string) $result);
    }

    public function testRenderWithMultipleTemplatesTriesFallback(): void
    {
        $obj = TwigViewableData::create();
        $obj->Title = 'Fallback';
        // First template doesn't exist, second does
        $result = $obj->renderWith(['nonexistent_xyz', 'test']);
        $this->assertInstanceOf(DBHTMLText::class, $result);
        $this->assertStringContainsString('Hello Fallback', (string) $result);
    }

    public function testGetTemplateListUsesHardcodedTemplates(): void
    {
        $obj = TwigViewableData::create();
        $obj->templates = ['show' => ['custom/template']];
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');
        $result = $reflection->invoke($obj, 'show');
        $this->assertSame(['custom/template'], $result);
    }

    public function testGetTemplateListFallsToIndex(): void
    {
        $obj = TwigViewableData::create();
        $obj->templates = ['index' => ['index/template']];
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');
        $result = $reflection->invoke($obj, 'nonexistent');
        $this->assertSame(['index/template'], $result);
    }

    public function testGetTemplateListFallsToTemplate(): void
    {
        $obj = TwigViewableData::create();
        $obj->template = 'single/template';
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');
        $result = $reflection->invoke($obj, null);
        $this->assertSame(['single/template'], $result);
    }

    public function testGetTemplateListBuildsFromClassName(): void
    {
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');
        $result = $reflection->invoke($obj, null);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testIssetReturnsTrueForDic(): void
    {
        $obj = TwigViewableData::create();
        // __isset returns true when hasMethod returns false
        $this->assertTrue(isset($obj->somethingThatIsNotAMethod));
    }
}
