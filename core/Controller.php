<?php

namespace Core;

use Twig\Environment;
use App\Models\Menu;
use App\Models\Site;

abstract class Controller
{
    protected Environment $twig;
    protected \PDO $db;
    protected array $site;

    public function __construct()
    {
        $this->twig = App::getTwig();
        $this->db = Database::getInstance();
        $this->site = App::getSite();
    }

    protected function render(string $template, array $data = []): void
    {
        // Auto-inject menus for front-end templates if not already provided
        if (!isset($data['header_menu']) || !isset($data['footer_menu'])) {
            $siteModel = new Site();
            $dbSite = $siteModel->findBySlug($this->site['slug']);
            if ($dbSite) {
                $menuModel = new Menu();
                if (!isset($data['header_menu'])) {
                    $data['header_menu'] = $menuModel->getByLocationAndSite('header', $dbSite['id']);
                }
                if (!isset($data['footer_menu'])) {
                    $data['footer_menu'] = $menuModel->getByLocationAndSite('footer', $dbSite['id']);
                }
            }
        }

        // Check if shop is enabled (any menu item links to /shop or /winkelwagen)
        $data['shop_enabled'] = false;
        if (!empty($data['header_menu']['items'])) {
            foreach ($data['header_menu']['items'] as $item) {
                $url = $item['url'] ?? '';
                if (str_starts_with($url, '/shop') || str_starts_with($url, '/winkelwagen')) {
                    $data['shop_enabled'] = true;
                    break;
                }
            }
        }

        $data = array_merge($data, [
            'site' => $this->site,
            'csrf_token' => Csrf::token(),
            'csrf_field' => Csrf::field(),
            'flash' => Session::getFlash(),
            'current_user' => Auth::user(),
            'current_url' => $_SERVER['REQUEST_URI'] ?? '/',
        ]);

        echo $this->twig->render($template, $data);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate(array $rules): array
    {
        $errors = [];
        $data = [];

        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $ruleList = explode('|', $rule);

            foreach ($ruleList as $r) {
                if ($r === 'required' && (is_null($value) || trim((string)$value) === '')) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is verplicht.';
                }
                if ($r === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Ongeldig e-mailadres.';
                }
                if (str_starts_with($r, 'min:')) {
                    $min = (int) substr($r, 4);
                    if ($value && strlen((string)$value) < $min) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " moet minimaal {$min} tekens zijn.";
                    }
                }
                if (str_starts_with($r, 'max:')) {
                    $max = (int) substr($r, 4);
                    if ($value && strlen((string)$value) > $max) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " mag maximaal {$max} tekens zijn.";
                    }
                }
                if ($r === 'numeric' && $value && !is_numeric($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' moet een getal zijn.';
                }
            }

            $data[$field] = $value;
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
