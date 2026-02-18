<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigContainer;
use Azt3k\SS\Twig\TwigSSGlobals;
use SilverStripe\Dev\SapphireTest;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigContainerTest extends SapphireTest
{
    protected $usesDatabase = false;

    private array $originalConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConfig = TwigContainer::getConfig();
    }

    protected function tearDown(): void
    {
        TwigContainer::setConfig($this->originalConfig);
        parent::tearDown();
    }

    public function testGetConfigReturnsArray(): void
    {
        $config = TwigContainer::getConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('twig.loader_class', $config);
        $this->assertArrayHasKey('twig.environment_options', $config);
        $this->assertArrayHasKey('twig.extensions', $config);
        $this->assertArrayHasKey('twig.controller_variable_name', $config);
    }

    public function testSetConfigReplacesConfig(): void
    {
        $newConfig = ['twig.controller_variable_name' => 'x'];
        TwigContainer::setConfig($newConfig);
        $this->assertSame($newConfig, TwigContainer::getConfig());
    }

    public function testExtendConfigMergesRecursively(): void
    {
        TwigContainer::extendConfig(['twig.globals' => ['foo' => 'bar']]);
        $config = TwigContainer::getConfig();
        $this->assertContains('bar', $config['twig.globals']);
    }

    public function testDefaultLoaderClass(): void
    {
        $config = TwigContainer::getConfig();
        $this->assertSame('\\Twig\\Loader\\FilesystemLoader', $config['twig.loader_class']);
    }

    public function testDefaultExtensions(): void
    {
        $config = TwigContainer::getConfig();
        $this->assertContains('.twig', $config['twig.extensions']);
    }

    public function testDefaultControllerVariableName(): void
    {
        $config = TwigContainer::getConfig();
        $this->assertSame('c', $config['twig.controller_variable_name']);
    }

    public function testDefaultGlobalsIsEmpty(): void
    {
        $config = TwigContainer::getConfig();
        $this->assertEmpty($config['twig.globals']);
    }

    public function testDefaultModuleTemplatePathsIsEmpty(): void
    {
        $config = TwigContainer::getConfig();
        $this->assertEmpty($config['twig.module_template_paths']);
    }

    public function testContainerCanBeInstantiated(): void
    {
        $container = new TwigContainer();
        $this->assertInstanceOf(TwigContainer::class, $container);
    }

    public function testContainerProvidesTwigEnvironment(): void
    {
        $container = new TwigContainer();
        $twig = $container['twig'];
        $this->assertInstanceOf(Environment::class, $twig);
    }

    public function testContainerProvidesTwigLoader(): void
    {
        $container = new TwigContainer();
        $loader = $container['twig.loader'];
        $this->assertInstanceOf(FilesystemLoader::class, $loader);
    }

    public function testContainerTemplatePathsAreArray(): void
    {
        $container = new TwigContainer();
        $this->assertIsArray($container['twig.template_paths']);
    }

    public function testTwigEnvironmentHasGlobalG(): void
    {
        $container = new TwigContainer();
        $twig = $container['twig'];
        $globals = $twig->getGlobals();
        $this->assertArrayHasKey('g', $globals);
        $this->assertInstanceOf(TwigSSGlobals::class, $globals['g']);
    }

    public function testTwigEnvironmentAutoReload(): void
    {
        $container = new TwigContainer();
        $twig = $container['twig'];
        $this->assertTrue($twig->isAutoReload());
    }

    public function testContainerWithModuleTemplatePaths(): void
    {
        $fixturesDir = dirname(__DIR__) . '/tests/fixtures';
        TwigContainer::extendConfig([
            'twig.module_template_paths' => [$fixturesDir],
        ]);
        $container = new TwigContainer();
        $paths = $container['twig.template_paths'];
        $this->assertContains($fixturesDir, $paths);
    }

    public function testContainerWithCustomGlobals(): void
    {
        TwigContainer::extendConfig([
            'twig.globals' => ['testGlobal' => 'testValue'],
        ]);
        $container = new TwigContainer();
        $twig = $container['twig'];
        $globals = $twig->getGlobals();
        $this->assertArrayHasKey('testGlobal', $globals);
        $this->assertSame('testValue', $globals['testGlobal']);
    }

    public function testContainerWithDebugMode(): void
    {
        TwigContainer::extendConfig([
            'twig.environment_options' => ['debug' => true],
        ]);
        $container = new TwigContainer();
        $twig = $container['twig'];
        $this->assertTrue($twig->isDebug());
    }

    public function testAddExtensionStoresExtension(): void
    {
        $called = false;
        TwigContainer::addExtension('twig', function ($twig, $c) use (&$called) {
            $called = true;
            return $twig;
        });
        $container = new TwigContainer();
        // Access twig to trigger the extension
        $twig = $container['twig'];
        $this->assertTrue($called);
    }

    public function testAddSharedStoresService(): void
    {
        TwigContainer::addShared('test.service', function ($c) {
            return 'test_value';
        });
        $container = new TwigContainer();
        $this->assertSame('test_value', $container['test.service']);
    }

    public function testCompilationCacheUsesTemp(): void
    {
        $container = new TwigContainer();
        $cache = $container['twig.compilation_cache'];
        $this->assertStringContainsString('twig-cache', $cache);
    }

    public function testContainerWithNamespacedPaths(): void
    {
        $fixturesDir = dirname(__DIR__) . '/tests/fixtures';
        $container = new TwigContainer();
        $container['twig.template_namespaced_paths'] = [$fixturesDir => 'test'];

        // Re-fetch the loader to trigger namespaced path addition
        $loader = $container['twig.loader'];
        $this->assertInstanceOf(FilesystemLoader::class, $loader);
    }
}
