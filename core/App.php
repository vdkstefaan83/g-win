<?php

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Core\Helpers\SiteResolver;
use Core\Helpers\LangResolver;
use Core\Middleware\CsrfMiddleware;
use Core\Middleware\AdminMiddleware;

class App
{
    private static Environment $twig;
    private static array $site;
    private static string $lang = 'nl';
    private static array $translations = [];
    private \Bramus\Router\Router $router;

    public function __construct()
    {
        // Resolve current site
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        self::$site = SiteResolver::resolve($host);

        // Resolve language from URL prefix (strips /fr/ from REQUEST_URI)
        self::$lang = LangResolver::resolve();

        // Load translations
        self::$translations = require dirname(__DIR__) . '/app/Config/lang.php';

        // Initialize Twig
        $loader = new FilesystemLoader(dirname(__DIR__) . '/views');
        self::$twig = new Environment($loader, [
            'cache' => dirname(__DIR__) . '/storage/cache/twig',
            'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            'auto_reload' => true,
        ]);

        // Register Twig globals and functions
        self::$twig->addGlobal('app_url', $_ENV['APP_URL'] ?? 'http://localhost');
        self::$twig->addGlobal('lang', self::$lang);
        self::$twig->addFunction(new TwigFunction('csrf_field', [Csrf::class, 'field'], ['is_safe' => ['html']]));
        self::$twig->addFunction(new TwigFunction('csrf_token', [Csrf::class, 'token']));
        self::$twig->addFunction(new TwigFunction('asset', function (string $path) {
            return '/assets/' . ltrim($path, '/');
        }));
        self::$twig->addFunction(new TwigFunction('upload_url', function (string $path) {
            return '/uploads/' . ltrim($path, '/');
        }));

        // Translation function: {{ t('key') }}
        self::$twig->addFunction(new TwigFunction('t', function (string $key) {
            return self::translate($key);
        }));

        // Language-aware URL function: {{ lang_url('/about') }}
        self::$twig->addFunction(new TwigFunction('lang_url', function (string $path) {
            return self::langUrl($path);
        }));

        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            self::$twig->addExtension(new \Twig\Extension\DebugExtension());
        }

        // Initialize router
        $this->router = new \Bramus\Router\Router();
    }

    public function run(): void
    {
        $this->registerRoutes();
        $this->router->run();
    }

    private function registerRoutes(): void
    {
        $router = $this->router;

        // CSRF middleware for all POST requests
        $router->before('POST', '/.*', function () {
            CsrfMiddleware::handle();
        });

        // Admin middleware
        $router->before('GET|POST', '/admin(?!/login)(/.*)?', function () {
            AdminMiddleware::handle();
        });

        // Load route definitions
        require dirname(__DIR__) . '/app/Config/routes.php';
    }

    public static function getTwig(): Environment
    {
        return self::$twig;
    }

    public static function getSite(): array
    {
        return self::$site;
    }

    public static function getLang(): string
    {
        return self::$lang;
    }

    public static function translate(string $key): string
    {
        return self::$translations[self::$lang][$key]
            ?? self::$translations['nl'][$key]
            ?? $key;
    }

    public static function langUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if (self::$lang !== 'nl') {
            // Don't add prefix if path already starts with the language prefix
            if (!str_starts_with($path, '/' . self::$lang . '/') && $path !== '/' . self::$lang) {
                return '/' . self::$lang . $path;
            }
        }
        return $path;
    }
}
