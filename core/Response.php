<?php

namespace Core;

class Response
{
    private array $headers = [];
    private int $statusCode = 200;
    private string $content = '';

    /**
     * Set response header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Set response status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Set response content
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Send HTML response
     */
    public function html(string $content, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode)
             ->setHeader('Content-Type', 'text/html; charset=UTF-8')
             ->setContent($content);
        $this->send();
    }

    /**
     * Send JSON response
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode)
             ->setHeader('Content-Type', 'application/json; charset=UTF-8')
             ->setContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->send();
    }

    /**
     * Send JSONP response
     */
    public function jsonp(array $data, string $callback = 'callback', int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode)
             ->setHeader('Content-Type', 'application/javascript; charset=UTF-8')
             ->setContent($callback . '(' . json_encode($data) . ');');
        $this->send();
    }

    /**
     * Send text response
     */
    public function text(string $content, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode)
             ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
             ->setContent($content);
        $this->send();
    }

    /**
     * Send XML response
     */
    public function xml(string $content, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode)
             ->setHeader('Content-Type', 'application/xml; charset=UTF-8')
             ->setContent($content);
        $this->send();
    }

    /**
     * Send file download response
     */
    public function download(string $filePath, string $fileName = null): void
    {
        if (!file_exists($filePath)) {
            $this->json(['error' => 'File not found'], 404);
            return;
        }

        $fileName = $fileName ?? basename($filePath);
        $fileSize = filesize($filePath);
        $mimeType = $this->getMimeType($filePath);

        $this->setHeader('Content-Type', $mimeType)
             ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
             ->setHeader('Content-Length', (string) $fileSize)
             ->setHeader('Cache-Control', 'no-cache, must-revalidate')
             ->setHeader('Pragma', 'no-cache');

        readfile($filePath);
        exit;
    }

    /**
     * Send file response (inline)
     */
    public function file(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->json(['error' => 'File not found'], 404);
            return;
        }

        $mimeType = $this->getMimeType($filePath);
        $fileSize = filesize($filePath);

        $this->setHeader('Content-Type', $mimeType)
             ->setHeader('Content-Length', (string) $fileSize)
             ->setHeader('Cache-Control', 'public, max-age=3600');

        readfile($filePath);
        exit;
    }

    /**
     * Redirect to another URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        if ($url !== '' && $url[0] === '/' && !str_starts_with($url, '//')) {
            $basePath = (string) Config::get('app.base_path', '');
            if ($basePath !== '' && $basePath !== '/') {
                $url = rtrim($basePath, '/') . $url;
            }
        }

        $this->setStatusCode($statusCode)
             ->setHeader('Location', $url);
        $this->send();
        exit;
    }

    /**
     * Send response with proper headers
     */
    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv'
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Set CORS headers
     */
    public function setCors(array $allowedOrigins = ['*'], array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'], array $allowedHeaders = ['Content-Type', 'Authorization']): self
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $this->setHeader('Access-Control-Allow-Origin', $origin);
        }

        $this->setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods))
             ->setHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders))
             ->setHeader('Access-Control-Allow-Credentials', 'true')
             ->setHeader('Access-Control-Max-Age', '86400');

        return $this;
    }

    /**
     * Send success response
     */
    public function success(mixed $data = null, string $message = 'Success'): void
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response);
    }

    /**
     * Send error response
     */
    public function error(string $message = 'Error', int $statusCode = 400, mixed $data = null): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($data !== null) {
            $response['errors'] = $data;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Send validation error response
     */
    public function validationError(array $errors, string $message = 'Validation failed'): void
    {
        $this->error($message, 422, $errors);
    }

    /**
     * Send unauthorized response
     */
    public function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, 403);
    }

    /**
     * Send not found response
     */
    public function notFound(string $message = 'Not found'): void
    {
        $this->error($message, 404);
    }

    /**
     * Send server error response
     */
    public function serverError(string $message = 'Internal server error'): void
    {
        $this->error($message, 500);
    }

    /**
     * Set cache headers
     */
    public function setCache(int $maxAge = 3600, bool $public = true): self
    {
        $control = $public ? 'public' : 'private';
        $this->setHeader('Cache-Control', "$control, max-age=$maxAge")
             ->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        return $this;
    }

    /**
     * Set no cache headers
     */
    public function setNoCache(): self
    {
        $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
             ->setHeader('Pragma', 'no-cache')
             ->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        return $this;
    }
}
