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
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we build templates from its class name
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class);

        // THEN it should return a non-empty array of template candidates
        $this->assertIsArray($templates);
        $this->assertNotEmpty($templates);
    }

    public function testBuildTemplatesFromClassNameIncludesClassName(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we build templates from its class name
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class);

        // THEN the class name (with backslashes converted to slashes) should be in the list
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData', $templates);
    }

    public function testBuildTemplatesFromClassNameIncludesParentClasses(): void
    {
        // GIVEN a TwigViewableData instance (which extends ModelData)
        $obj = TwigViewableData::create();

        // WHEN we build templates from its class name
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class);

        // THEN both the class and its parent should be in the candidate list
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData', $templates);
        $this->assertContains('SilverStripe/Model/ModelData', $templates);
    }

    public function testBuildTemplatesFromClassNameWithAction(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we build templates with a 'show' action
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class, 'show');

        // THEN action-specific templates (ClassName_action) should be included
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData_show', $templates);
    }

    public function testBuildTemplatesFromClassNameIndexActionIgnored(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we build templates with the 'index' action
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class, 'index');

        // THEN index-specific templates should NOT be generated (index is the default)
        $this->assertNotContains('Azt3k/SS/Twig/TwigViewableData_index', $templates);
    }

    public function testBuildTemplatesFromClassNameStopsAtController(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we build templates starting from Controller class itself
        $templates = $obj->buildTemplatesFromClassName(Controller::class);

        // THEN it should return empty (Controller is the stop boundary)
        $this->assertEmpty($templates);
    }

    public function testBuildTemplatesFromClassNameWithNullAction(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we build templates with null action
        $templates = $obj->buildTemplatesFromClassName(TwigViewableData::class, null);

        // THEN it should return an array (no action-specific templates, just class hierarchy)
        $this->assertIsArray($templates);
    }

    public function testCustomiseWithArraySetsProperties(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we customise it with an array of properties
        $result = $obj->customise(['TestProp' => 'value']);

        // THEN the properties should be set directly on the object and it returns self
        $this->assertSame($obj, $result);
        $this->assertSame('value', $obj->TestProp);
    }

    public function testCustomiseReturnsSelf(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we customise it
        $result = $obj->customise(['key' => 'val']);

        // THEN it should return the same instance (for method chaining)
        $this->assertInstanceOf(TwigViewableData::class, $result);
    }

    public function testDicPropertyCreatesTwigContainer(): void
    {
        // GIVEN a TwigViewableData instance with no dic property set
        $obj = TwigViewableData::create();

        // WHEN we access the dic property (via __get magic method)
        $dic = $obj->dic;

        // THEN it should lazily create and return a TwigContainer
        $this->assertInstanceOf(TwigContainer::class, $dic);
    }

    public function testIncludeRequirementsDefaultsToTrue(): void
    {
        // GIVEN a fresh TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we inspect the protected includeRequirements property
        $reflection = new \ReflectionProperty($obj, 'includeRequirements');

        // THEN it should default to true (Requirements::includeInHTML will be called)
        $this->assertTrue($reflection->getValue($obj));
    }

    public function testRenderTwigWithTemplate(): void
    {
        // GIVEN a TwigViewableData with a Title property and test fixtures registered
        $obj = TwigViewableData::create();
        $obj->Title = 'World';

        // WHEN we call the protected renderTwig method with the 'test' template
        $reflection = new \ReflectionMethod($obj, 'renderTwig');
        $result = $reflection->invoke($obj, ['test'], $obj);

        // THEN the output should contain the rendered template with the Title variable
        $this->assertStringContainsString('Hello World', $result);
    }

    public function testRenderTwigThrowsForMissingTemplate(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'renderTwig');

        // WHEN we try to render a template that doesn't exist
        // THEN an InvalidArgumentException should be thrown
        $this->expectException(\InvalidArgumentException::class);
        $reflection->invoke($obj, ['nonexistent_template_xyz'], $obj);
    }

    public function testGetTwigTemplateReturnsTemplateWrapper(): void
    {
        // GIVEN a TwigViewableData with test fixtures registered
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTwigTemplate');

        // WHEN we resolve the 'test' template
        $template = $reflection->invoke($obj, ['test']);

        // THEN it should return a Twig TemplateWrapper ready for rendering
        $this->assertInstanceOf(\Twig\TemplateWrapper::class, $template);
    }

    public function testGetTwigTemplateThrowsForEmptyArray(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTwigTemplate');

        // WHEN we pass an empty template array
        // THEN an InvalidArgumentException should be thrown
        $this->expectException(\InvalidArgumentException::class);
        $reflection->invoke($obj, []);
    }

    public function testGetTwigTemplateHandlesNestedArrayTemplates(): void
    {
        // GIVEN a TwigViewableData with test fixtures registered
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTwigTemplate');

        // WHEN we pass a nested array format (as SSViewer produces for Includes)
        $template = $reflection->invoke($obj, [['type' => 'Includes', 0 => 'test']]);

        // THEN it should extract the template name from index 0 and resolve it
        $this->assertInstanceOf(\Twig\TemplateWrapper::class, $template);
    }

    public function testRenderWithReturnDBHTMLText(): void
    {
        // GIVEN a TwigViewableData with a Title property
        $obj = TwigViewableData::create();
        $obj->Title = 'Test';

        // WHEN we call renderWith (the SS6 public API)
        $result = $obj->renderWith(['test']);

        // THEN it should return a DBHTMLText instance containing the rendered output
        $this->assertInstanceOf(DBHTMLText::class, $result);
        $this->assertStringContainsString('Hello Test', (string) $result);
    }

    public function testRenderWithMultipleTemplatesTriesFallback(): void
    {
        // GIVEN a TwigViewableData with a Title property
        $obj = TwigViewableData::create();
        $obj->Title = 'Fallback';

        // WHEN we call renderWith with a non-existent template first, then a valid one
        $result = $obj->renderWith(['nonexistent_xyz', 'test']);

        // THEN the second (fallback) template should be used
        $this->assertInstanceOf(DBHTMLText::class, $result);
        $this->assertStringContainsString('Hello Fallback', (string) $result);
    }

    public function testGetTemplateListUsesHardcodedTemplates(): void
    {
        // GIVEN a TwigViewableData with a hardcoded templates array for 'show' action
        $obj = TwigViewableData::create();
        $obj->templates = ['show' => ['custom/template']];
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');

        // WHEN we request the template list for 'show'
        $result = $reflection->invoke($obj, 'show');

        // THEN it should return the hardcoded templates (not auto-generated ones)
        $this->assertSame(['custom/template'], $result);
    }

    public function testGetTemplateListFallsToIndex(): void
    {
        // GIVEN a TwigViewableData with only an 'index' template hardcoded
        $obj = TwigViewableData::create();
        $obj->templates = ['index' => ['index/template']];
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');

        // WHEN we request a template list for a non-existent action
        $result = $reflection->invoke($obj, 'nonexistent');

        // THEN it should fall back to the 'index' templates
        $this->assertSame(['index/template'], $result);
    }

    public function testGetTemplateListFallsToTemplate(): void
    {
        // GIVEN a TwigViewableData with a single template string set
        $obj = TwigViewableData::create();
        $obj->template = 'single/template';
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');

        // WHEN we request the template list with no action
        $result = $reflection->invoke($obj, null);

        // THEN it should wrap the string in an array
        $this->assertSame(['single/template'], $result);
    }

    public function testGetTemplateListBuildsFromClassName(): void
    {
        // GIVEN a TwigViewableData with no hardcoded templates
        $obj = TwigViewableData::create();
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');

        // WHEN we request the template list with no action
        $result = $reflection->invoke($obj, null);

        // THEN it should auto-build a list from the class hierarchy
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testIssetReturnsTrueForDic(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we check isset for a property that isn't a method
        // THEN __isset returns true (allows dynamic property access via Twig)
        $this->assertTrue(isset($obj->somethingThatIsNotAMethod));
    }

    public function testCustomiseWithNonArrayReturnsself(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();

        // WHEN we customise with a non-array value (e.g. a string)
        $result = $obj->customise('not-an-array');

        // THEN it should return self without error (no-op for non-arrays)
        $this->assertSame($obj, $result);
    }

    public function testGetTemplateListWithTemplateAsArray(): void
    {
        // GIVEN a TwigViewableData with $template set as an array
        $obj = TwigViewableData::create();
        $obj->template = ['first/template', 'second/template'];
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');

        // WHEN we request the template list with no action
        $result = $reflection->invoke($obj, null);

        // THEN it should return the array as-is
        $this->assertContains('first/template', $result);
        $this->assertContains('second/template', $result);
    }

    public function testGetTemplateListWithDifferentClassName(): void
    {
        // GIVEN a TwigViewableData where ClassName differs from get_class()
        // (using a real class so get_parent_class() doesn't throw in PHP 8.5)
        $obj = TwigViewableData::create();
        $obj->ClassName = Controller::class;
        $reflection = new \ReflectionMethod($obj, 'getTemplateList');

        // WHEN we request the template list with no action
        $result = $reflection->invoke($obj, null);

        // THEN both the actual class hierarchy and ClassName hierarchy should be merged
        $this->assertContains('Azt3k/SS/Twig/TwigViewableData', $result);
    }

    public function testRenderWithFallsBackToParentForMissingTwigTemplate(): void
    {
        // GIVEN a TwigViewableData instance with no matching twig template
        $obj = TwigViewableData::create();

        // WHEN we call renderWith with a template that has no .twig file
        // THEN it should catch InvalidArgumentException from Twig and fall back to parent
        // (parent may also throw MissingTemplateException if no .ss template exists either)
        try {
            $result = $obj->renderWith(['SilverStripe\\Model\\ModelData']);
            $this->assertInstanceOf(DBHTMLText::class, $result);
        } catch (\SilverStripe\View\Exception\MissingTemplateException $e) {
            // THEN the fallback to parent renderWith was attempted (confirming the catch block ran)
            $this->assertStringContainsString('ModelData', $e->getMessage());
        }
    }

    public function testRenderTwigWithoutRequirements(): void
    {
        // GIVEN a TwigViewableData with includeRequirements disabled
        $obj = TwigViewableData::create();
        $obj->Title = 'NoReq';
        $ref = new \ReflectionProperty($obj, 'includeRequirements');
        $ref->setValue($obj, false);

        // WHEN we call renderTwig
        $method = new \ReflectionMethod($obj, 'renderTwig');
        $result = $method->invoke($obj, ['test'], $obj);

        // THEN it should still render the template (without Requirements::includeInHTML)
        $this->assertStringContainsString('Hello NoReq', $result);
    }

    public function testApplyExtensionResultReturnsNullWhenNoExtensions(): void
    {
        // GIVEN a TwigViewableData instance with no extensions registered
        $obj = TwigViewableData::create();
        $method = new \ReflectionMethod($obj, 'applyExtensionResult');

        // WHEN we call applyExtensionResult with a hook that has no handlers
        $result = $method->invoke($obj, 'SomeNonExistentHook', 'arg');

        // THEN it should return null (no extension results)
        $this->assertNull($result);
    }

    public function testResolveTemplateNameWithArrayEntry(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();
        $method = new \ReflectionMethod($obj, 'resolveTemplateName');

        // WHEN we resolve a template entry in SS-style array format
        $result = $method->invoke($obj, ['type' => 'Includes', 0 => 'My/Template']);

        // THEN it should extract the template name from index 0
        $this->assertSame('My/Template', $result);
    }

    public function testResolveTemplateNameWithString(): void
    {
        // GIVEN a TwigViewableData instance
        $obj = TwigViewableData::create();
        $method = new \ReflectionMethod($obj, 'resolveTemplateName');

        // WHEN we resolve a simple string template name
        $result = $method->invoke($obj, 'Simple/Template');

        // THEN it should return the string unchanged
        $this->assertSame('Simple/Template', $result);
    }

    public function testFindLoadableTemplateReturnsNullForMissing(): void
    {
        // GIVEN a TwigViewableData with a container
        $obj = TwigViewableData::create();
        $loader = $obj->dic['twig.loader'];
        $method = new \ReflectionMethod($obj, 'findLoadableTemplate');

        // WHEN we try to find a template that doesn't exist
        $result = $method->invoke($obj, $loader, ['.twig'], 'nonexistent_xyz_template');

        // THEN it should return null
        $this->assertNull($result);
    }

    public function testFindLoadableTemplateReturnsTemplateForExisting(): void
    {
        // GIVEN a TwigViewableData with test fixtures registered
        $obj = TwigViewableData::create();
        $loader = $obj->dic['twig.loader'];
        $method = new \ReflectionMethod($obj, 'findLoadableTemplate');

        // WHEN we try to find the 'test' template (which exists in fixtures)
        $result = $method->invoke($obj, $loader, ['.twig'], 'test');

        // THEN it should return a TemplateWrapper
        $this->assertInstanceOf(\Twig\TemplateWrapper::class, $result);
    }
}
