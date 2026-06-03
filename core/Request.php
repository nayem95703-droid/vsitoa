<?php

namespace Core;

class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private array $headers;
    private string $method;
    private string $path;
    private string $uri;
    private array $routeParams = [];

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->headers = $this->getAllHeaders();
        $this->method = $this->server['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->server['REQUEST_URI'] ?? '/';
        $this->path = $this->extractPath();

        if (in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $contentType = (string) ($this->header('Content-Type') ?? '');
            if ($contentType !== '' && stripos($contentType, 'application/json') !== false) {
                $json = $this->json();
                if (!empty($json) && is_array($json)) {
                    $this->post = array_merge($this->post, $json);
                }
            }
        }
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get request path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set route params (assigned by Router)
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get a single route param
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Get all route params
     */
    public function params(): array
    {
        return $this->routeParams;
    }

    /**
     * Get full URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get query string
     */
    public function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    /**
     * Get GET parameter
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get all GET parameters
     */
    public function query(): array
    {
        return $this->get;
    }

    /**
     * Get POST parameter
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all POST parameters
     */
    public function body(): array
    {
        return $this->post;
    }

    /**
     * Get input from GET or POST
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    /**
     * Get all input (GET + POST)
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * Check if input exists
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->get[$key]);
    }

    /**
     * Get JSON input
     */
    public function json(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get all uploaded files
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Get header
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtoupper(trim($key));
        if (array_key_exists($normalized, $this->headers)) {
            return $this->headers[$normalized];
        }

        $dashKey = str_replace('_', '-', $normalized);
        if (array_key_exists($dashKey, $this->headers)) {
            return $this->headers[$dashKey];
        }

        $underscoreKey = str_replace('-', '_', $normalized);
        if (array_key_exists($underscoreKey, $this->headers)) {
            return $this->headers[$underscoreKey];
        }

        return $default;
    }

    /**
     * Get all headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get server variable
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($this->server[$key])) {
                $ips = explode(',', $this->server[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get user agent
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return !empty($this->server['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return !empty($this->server['HTTP_ACCEPT']) && 
               strpos($this->server['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /**
     * Check if request is HTTPS
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') ||
               (!empty($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Get referer
     */
    public function referer(): string
    {
        return $this->server['HTTP_REFERER'] ?? '';
    }

    /**
     * Get base URL
     */
    public function baseUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return "$scheme://$host";
    }

    /**
     * Get full URL
     */
    public function fullUrl(): string
    {
        return $this->baseUrl() . $this->uri;
    }

    /**
     * Extract path from URI
     */
    private function extractPath(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        return $path ?: '/';
    }

    /**
     * Get all headers
     */
    private function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return array_change_key_case($headers, CASE_UPPER);
        }

        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerKey = str_replace('HTTP_', '', $key);
                $headerKey = str_replace('_', ' ', $headerKey);
                $headerKey = ucwords(strtolower($headerKey));
                $headerKey = str_replace(' ', '-', $headerKey);
                $headers[$headerKey] = $value;
            }
        }

        return array_change_key_case($headers, CASE_UPPER);
    }

    /**
     * Validate CSRF token
     */
    public function validateCsrf(): bool
    {
        $token = $this->post('_token') ?? $this->header('X-CSRF-TOKEN');
        return $token && $token === ($_SESSION['_token'] ?? '');
    }

    /**
     * Get current timestamp
     */
    public function timestamp(): int
    {
        return time();
    }

    /**
     * Get request ID for tracking
     */
    public function requestId(): string
    {
        return uniqid('req_', true);
    }
}
