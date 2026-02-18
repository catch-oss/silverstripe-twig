<?php

namespace Azt3k\SS\Twig\Tests;

use Azt3k\SS\Twig\TwigContainer;
use Azt3k\SS\Twig\TwigSSGlobals;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\SSViewer;
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
        // GIVEN the default TwigContainer static config
        // WHEN we retrieve the config
        $config = TwigContainer::getConfig();

        // THEN it should be an array containing all required keys
        $this->assertIsArray($config);
        $this->assertArrayHasKey('twig.loader_class', $config);
        $this->assertArrayHasKey('twig.environment_options', $config);
        $this->assertArrayHasKey('twig.extensions', $config);
        $this->assertArrayHasKey('twig.controller_variable_name', $config);
    }

    public function testSetConfigReplacesConfig(): void
    {
        // GIVEN a new config array with a single key
        $newConfig = ['twig.controller_variable_name' => 'x'];

        // WHEN we replace the entire config
        TwigContainer::setConfig($newConfig);

        // THEN the config should be exactly the new array, not merged
        $this->assertSame($newConfig, TwigContainer::getConfig());
    }

    public function testExtendConfigMergesRecursively(): void
    {
        // GIVEN the default config with empty globals
        // WHEN we extend config with a new global
        TwigContainer::extendConfig(['twig.globals' => ['foo' => 'bar']]);

        // THEN the global should be merged into the existing config
        $config = TwigContainer::getConfig();
        $this->assertContains('bar', $config['twig.globals']);
    }

    public function testDefaultLoaderClass(): void
    {
        // GIVEN the default TwigContainer config
        // WHEN we read the loader class
        $config = TwigContainer::getConfig();

        // THEN it should be the Twig 3 namespaced FilesystemLoader
        $this->assertSame('\\Twig\\Loader\\FilesystemLoader', $config['twig.loader_class']);
    }

    public function testDefaultExtensions(): void
    {
        // GIVEN the default TwigContainer config
        // WHEN we read the file extensions
        $config = TwigContainer::getConfig();

        // THEN .twig should be a registered extension
        $this->assertContains('.twig', $config['twig.extensions']);
    }

    public function testDefaultControllerVariableName(): void
    {
        // GIVEN the default TwigContainer config
        // WHEN we read the controller variable name
        $config = TwigContainer::getConfig();

        // THEN the controller is accessible as 'c' in templates
        $this->assertSame('c', $config['twig.controller_variable_name']);
    }

    public function testDefaultGlobalsIsEmpty(): void
    {
        // GIVEN the default TwigContainer config
        // WHEN we read the globals
        $config = TwigContainer::getConfig();

        // THEN no custom globals should be registered by default
        $this->assertEmpty($config['twig.globals']);
    }

    public function testDefaultModuleTemplatePathsIsEmpty(): void
    {
        // GIVEN the default TwigContainer config
        // WHEN we read the module template paths
        $config = TwigContainer::getConfig();

        // THEN no module paths should be registered by default
        $this->assertEmpty($config['twig.module_template_paths']);
    }

    public function testContainerCanBeInstantiated(): void
    {
        // GIVEN the default config
        // WHEN we create a new TwigContainer
        $container = new TwigContainer();

        // THEN it should be a valid TwigContainer instance
        $this->assertInstanceOf(TwigContainer::class, $container);
    }

    public function testContainerProvidesTwigEnvironment(): void
    {
        // GIVEN a freshly instantiated container
        $container = new TwigContainer();

        // WHEN we access the 'twig' service
        $twig = $container['twig'];

        // THEN it should be a Twig Environment instance
        $this->assertInstanceOf(Environment::class, $twig);
    }

    public function testContainerProvidesTwigLoader(): void
    {
        // GIVEN a freshly instantiated container
        $container = new TwigContainer();

        // WHEN we access the 'twig.loader' service
        $loader = $container['twig.loader'];

        // THEN it should be a FilesystemLoader
        $this->assertInstanceOf(FilesystemLoader::class, $loader);
    }

    public function testContainerTemplatePathsAreArray(): void
    {
        // GIVEN a freshly instantiated container
        $container = new TwigContainer();

        // WHEN we access the resolved template paths
        // THEN it should be an array (possibly empty if no paths exist on disk)
        $this->assertIsArray($container['twig.template_paths']);
    }

    public function testTwigEnvironmentHasGlobalG(): void
    {
        // GIVEN a container with a Twig environment
        $container = new TwigContainer();
        $twig = $container['twig'];

        // WHEN we read the registered globals
        $globals = $twig->getGlobals();

        // THEN 'g' should be a TwigSSGlobals instance (SS template globals)
        $this->assertArrayHasKey('g', $globals);
        $this->assertInstanceOf(TwigSSGlobals::class, $globals['g']);
    }

    public function testTwigEnvironmentAutoReload(): void
    {
        // GIVEN the default environment options include auto_reload: true
        $container = new TwigContainer();

        // WHEN we check the Twig environment's auto-reload setting
        $twig = $container['twig'];

        // THEN auto-reload should be enabled
        $this->assertTrue($twig->isAutoReload());
    }

    public function testContainerWithModuleTemplatePaths(): void
    {
        // GIVEN a module template path pointing to the test fixtures directory
        $fixturesDir = dirname(__DIR__) . '/tests/fixtures';
        TwigContainer::extendConfig([
            'twig.module_template_paths' => [$fixturesDir],
        ]);

        // WHEN we instantiate the container and read the resolved paths
        $container = new TwigContainer();
        $paths = $container['twig.template_paths'];

        // THEN the fixtures directory should be in the resolved paths
        $this->assertContains($fixturesDir, $paths);
    }

    public function testContainerWithCustomGlobals(): void
    {
        // GIVEN a custom global variable configured
        TwigContainer::extendConfig([
            'twig.globals' => ['testGlobal' => 'testValue'],
        ]);

        // WHEN we instantiate the container and read Twig's globals
        $container = new TwigContainer();
        $twig = $container['twig'];
        $globals = $twig->getGlobals();

        // THEN the custom global should be available in templates
        $this->assertArrayHasKey('testGlobal', $globals);
        $this->assertSame('testValue', $globals['testGlobal']);
    }

    public function testContainerWithDebugMode(): void
    {
        // GIVEN debug mode enabled in environment options
        TwigContainer::extendConfig([
            'twig.environment_options' => ['debug' => true],
        ]);

        // WHEN we instantiate the container
        $container = new TwigContainer();
        $twig = $container['twig'];

        // THEN the Twig environment should be in debug mode (with DebugExtension added)
        $this->assertTrue($twig->isDebug());
    }

    public function testAddExtensionStoresExtension(): void
    {
        // GIVEN a Pimple extension callback registered for the 'twig' service
        $called = false;
        TwigContainer::addExtension('twig', function ($twig, $c) use (&$called) {
            $called = true;
            return $twig;
        });

        // WHEN we instantiate the container and access the twig service
        $container = new TwigContainer();
        $twig = $container['twig'];

        // THEN the extension callback should have been invoked
        $this->assertTrue($called);
    }

    public function testAddSharedStoresService(): void
    {
        // GIVEN a custom shared service registered
        TwigContainer::addShared('test.service', function ($c) {
            return 'test_value';
        });

        // WHEN we instantiate the container and access the service
        $container = new TwigContainer();

        // THEN the service should return the expected value
        $this->assertSame('test_value', $container['test.service']);
    }

    public function testCompilationCacheUsesTemp(): void
    {
        // GIVEN a freshly instantiated container
        $container = new TwigContainer();

        // WHEN we read the compilation cache path
        $cache = $container['twig.compilation_cache'];

        // THEN it should be under TEMP_PATH with 'twig-cache' suffix
        $this->assertStringContainsString('twig-cache', $cache);
    }

    public function testTemplatePathsIncludesExistingAppTwig(): void
    {
        // GIVEN the app/twig directory exists on disk
        $appTwig = BASE_PATH . '/app/twig';
        @mkdir($appTwig, 0755, true);

        try {
            // WHEN we instantiate the container
            $container = new TwigContainer();
            $paths = $container['twig.template_paths'];

            // THEN app/twig should be in the resolved template paths
            $this->assertContains($appTwig, $paths);
        } finally {
            @rmdir($appTwig);
        }
    }

    public function testTemplatePathsIncludesExistingAppTemplates(): void
    {
        // GIVEN the app/templates directory exists on disk
        $appTemplates = BASE_PATH . '/app/templates';
        $created = !is_dir($appTemplates);
        if ($created) {
            @mkdir($appTemplates, 0755, true);
        }

        try {
            // WHEN we instantiate the container
            $container = new TwigContainer();
            $paths = $container['twig.template_paths'];

            // THEN app/templates should be in the resolved template paths
            $this->assertContains($appTemplates, $paths);
        } finally {
            if ($created) {
                @rmdir($appTemplates);
            }
        }
    }

    public function testTemplatePathsExcludesNonExistentDirectories(): void
    {
        // GIVEN a freshly instantiated container
        $container = new TwigContainer();

        // WHEN we read the resolved template paths
        $paths = $container['twig.template_paths'];

        // THEN every path in the list should actually exist on disk
        $this->assertIsArray($paths);
        foreach ($paths as $path) {
            $this->assertDirectoryExists($path);
        }
    }

    public function testTemplatePathsIncludesThemeTwigDir(): void
    {
        // GIVEN a theme configured in SSViewer with a twig/ directory on disk
        $themes = SSViewer::get_themes();
        $testTheme = null;
        foreach ($themes as $theme) {
            if (!str_starts_with($theme, '$')) {
                $testTheme = $theme;
                break;
            }
        }
        if (!$testTheme) {
            $testTheme = 'testtheme';
            Config::modify()->set(SSViewer::class, 'themes', [$testTheme, '$default']);
        }

        $themeTwigDir = THEMES_PATH . '/' . $testTheme . '/twig';
        $createdDirs = [];
        $dir = $themeTwigDir;
        while (!is_dir($dir)) {
            array_unshift($createdDirs, $dir);
            $dir = dirname($dir);
        }
        foreach ($createdDirs as $d) {
            mkdir($d, 0755);
        }

        try {
            // WHEN we instantiate the container
            $container = new TwigContainer();
            $paths = $container['twig.template_paths'];

            // THEN the theme's twig/ directory should be in the resolved paths
            $this->assertContains($themeTwigDir, $paths);
        } finally {
            foreach (array_reverse($createdDirs) as $d) {
                @rmdir($d);
            }
        }
    }

    public function testTemplatePathsSkipsSpecialThemes(): void
    {
        // GIVEN only special themes ($default, $public) are configured
        Config::modify()->set(SSViewer::class, 'themes', ['$default', '$public']);

        // WHEN we instantiate the container
        $container = new TwigContainer();
        $paths = $container['twig.template_paths'];

        // THEN no paths should reference $default or $public theme directories
        $this->assertIsArray($paths);
        foreach ($paths as $path) {
            $this->assertStringNotContainsString('/$default/', $path);
            $this->assertStringNotContainsString('/$public/', $path);
        }
    }

    public function testContainerWithNamespacedPaths(): void
    {
        // GIVEN a container with a namespaced template path configured
        $fixturesDir = dirname(__DIR__) . '/tests/fixtures';
        $container = new TwigContainer();
        $container['twig.template_namespaced_paths'] = [$fixturesDir => 'test'];

        // WHEN we access the loader (which registers namespaced paths)
        $loader = $container['twig.loader'];

        // THEN the loader should be a FilesystemLoader (with the namespace registered)
        $this->assertInstanceOf(FilesystemLoader::class, $loader);
    }
}
