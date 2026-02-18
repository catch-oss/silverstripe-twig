<?php

namespace Azt3k\SS\Twig;

use Pimple\Container;
use Twig\Environment;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Extra\Cache\CacheExtension;
use Twig\Extra\Cache\CacheRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use SilverStripe\Core\Environment as SSEnvironment;
use SilverStripe\View\SSViewer;

class TwigContainer extends Container
{
    /**
     * Default config of properties
     * @var array
     */
    protected static $config = [
        // Updated for Twig 3: use namespaced FilesystemLoader
        'twig.loader_class' => '\\Twig\\Loader\\FilesystemLoader',
        'twig.environment_options' => array(
            'auto_reload' => true
        ),
        'twig.extensions' => array(
            '.twig'
        ),
        'twig.controller_variable_name' => 'c',
        "twig.globals"  => [],
        "twig.module_template_paths" => []
    ];

    /**
     * Holds user configured extensions of services
     * @var array
     */
    protected static $extensions = [];

    /**
     * Holds user configured shared services
     * @var array
     */
    protected static $shared = [];

    /**
     * Constructs the container and set up default services and properties
     */
    public function __construct() {

        parent::__construct();

        $this['twig'] = fn($c) => $this->createTwigEnvironment($c);
        $this['twig.loader'] = fn($c) => $this->createTwigLoader($c);
        $this['twig.compilation_cache'] = TEMP_PATH . '/twig-cache';
        $this['twig.template_paths'] = $this->resolveTemplatePaths();

        $this->applyUserConfig();
    }

    /**
     * Creates and configures the Twig Environment with globals, debug, and cache extensions
     */
    private function createTwigEnvironment(Container $c): Environment
    {
        $envOptions = array_merge(
            ['cache' => $c['twig.compilation_cache']],
            $c['twig.environment_options']
        );

        $twig = new Environment($c['twig.loader'], $envOptions);

        if (isset($c['twig.globals'])) {
            foreach ($c['twig.globals'] as $global => $path) {
                $twig->addGlobal($global, $path);
            }
        }

        $twig->addGlobal('g', new TwigSSGlobals());

        if (!empty($envOptions['debug'])) {
            $twig->addExtension(new DebugExtension());
        }

        $twig->addExtension(new CacheExtension());
        $twig->addRuntimeLoader($this->createCacheRuntimeLoader());

        return $twig;
    }

    /**
     * Creates the Twig filesystem loader with optional namespaced paths
     */
    private function createTwigLoader(Container $c): FilesystemLoader
    {
        $twigLoader = new $c['twig.loader_class']($c['twig.template_paths']);

        if (isset($c['twig.template_namespaced_paths'])) {
            foreach ($c['twig.template_namespaced_paths'] as $path => $namespace) {
                $twigLoader->addPath($path, $namespace);
            }
        }

        return $twigLoader;
    }

    /**
     * Creates the cache runtime loader, using NullAdapter when DISABLE_TWIG_FILE_CACHING is set
     */
    private function createCacheRuntimeLoader(): RuntimeLoaderInterface
    {
        $cacheAdapter = SSEnvironment::getEnv('DISABLE_TWIG_FILE_CACHING')
            ? new NullAdapter()
            : new PhpFilesAdapter('', 0, BASE_PATH . '/twig-partial-cache');

        return new class($cacheAdapter) implements RuntimeLoaderInterface {
            private $cacheAdapter;
            public function __construct($cacheAdapter) { $this->cacheAdapter = $cacheAdapter; }
            public function load($class) {
                if ($class === CacheRuntime::class) {
                    return new CacheRuntime($this->cacheAdapter);
                }
                return null;
            }
        };
    }

    /**
     * Builds the list of template paths from themes, app dirs, and module config,
     * filtering to only directories that exist on disk
     */
    private function resolveTemplatePaths(): array
    {
        $possiblePaths = [];

        foreach (SSViewer::get_themes() as $theme) {
            if (str_starts_with($theme, '$')) {
                continue;
            }
            $possiblePaths[] = THEMES_PATH . '/' . $theme . '/twig';
        }

        $possiblePaths[] = BASE_PATH . '/app/twig';
        $possiblePaths[] = BASE_PATH . '/app/templates';
        $possiblePaths[] = BASE_PATH . '/node_modules';

        $possiblePaths = array_merge(
            self::$config['twig.module_template_paths'],
            $possiblePaths,
        );

        return array_values(array_filter($possiblePaths, 'is_dir'));
    }

    /**
     * Applies default config values, user-registered extensions, and shared services
     */
    private function applyUserConfig(): void
    {
        foreach (self::$config as $key => $value) {
            $this[$key] = $value;
        }

        foreach (self::$extensions as $value) {
            $this->extend($value[0], $value[1]);
        }

        foreach (self::$shared as $value) {
            $this[$value[0]] = $value[1];
        }
    }

    /**
     * Alows the extending of already defined services by the user
     * @param string  $name      Name of service
     * @param Closure $extension Extending function
     */
    public static function addExtension(string $name, callable $extension): void {
        self::$extensions[] = array($name, $extension);
    }

    /**
     * Allows the adding of a shared service by the user
     * @param string  $name   Name of service
     * @param Closure $shared The shared service function
     */
    public static function addShared(string $name, callable $shared): void {
        self::$shared[] = array($name, $shared);
    }

    /**
     * Allows the addition to the default config by the user
     * @param array $config The extending config
     */
    public static function extendConfig(array $config): void {
        self::$config = array_merge_recursive(self::$config, $config);
    }

    /**
     * gets the current config
     * @return array the $config
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * sets the current config
     * @param array $config the config
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }
}
