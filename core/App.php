<?php

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Core\Helpers\SiteResolver;
use Core\Middleware\CsrfMiddleware;
use Core\Middleware\AdminMiddleware;

class App
{
    private static Environment $twig;
    private static array $site;
    private \Bramus\Router\Router $router;

    public function __construct()
    {
        // Resolve current site
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        self::$site = SiteResolver::resolve($host);

        // Initialize Twig
        $loader = new FilesystemLoader(dirname(__DIR__) . '/views');
        self::$twig = new Environment($loader, [
            'cache' => dirname(__DIR__) . '/storage/cache/twig',
            'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            'auto_reload' => true,
        ]);

        // Register Twig globals and functions
        self::$twig->addGlobal('app_url', $_ENV['APP_URL'] ?? 'http://localhost');
        self::$twig->addFunction(new TwigFunction('csrf_field', [Csrf::class, 'field'], ['is_safe' => ['html']]));
        self::$twig->addFunction(new TwigFunction('csrf_token', [Csrf::class, 'token']));
        self::$twig->addFunction(new TwigFunction('asset', function (string $path) {
            return '/assets/' . ltrim($path, '/');
        }));
        self::$twig->addFunction(new TwigFunction('upload_url', function (string $path) {
            return '/uploads/' . ltrim($path, '/');
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
}
