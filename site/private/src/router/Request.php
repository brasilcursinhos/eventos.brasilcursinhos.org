<?php
// Classe responsável por armazenar os dados da requisição enviada pelo cliente
namespace Router;

class Request
{
    protected $base;
    protected $uri;
    protected $method;
    protected $contentType;
    protected $protocol;
    protected $host;
    protected $data = [];
    protected $files = [];
    protected $headers = [];
    protected $rawBody = '';
 
    public function __construct()
    {
        $this->base = $_SERVER['REQUEST_URI'];
        $this->uri  = $_REQUEST['uri'] ?? '/';
        $this->method = strtolower($_SERVER['REQUEST_METHOD'] ?? '');
        $this->contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        $this->protocol = $this->isSecure() ? 'https' : 'http';
        $this->host = (PRODUCTION_MODE)? SITE_HTTP_HOST:($_SERVER['HTTP_HOST'] ?? 'localhost');
        $this->headers = $this->getAllHeaders();
        $this->setData();
 
        if(count($_FILES) > 0) {
            $this->setFiles();
        }
 
    }

    public function isSecure(): bool
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        return $isHttps;
    }

    protected function setData()
    {
        switch($this->method)
        {
            case 'post':
            case 'put':
            case 'delete':
            case 'patch':
                $this->rawBody = file_get_contents('php://input');

                $isJson = strpos($this->contentType, 'application/json') !== false 
                       || strpos($this->contentType, 'application/csp-report') !== false
                       || strpos($this->contentType, 'application/reports+json') !== false;

                if ($isJson) {
                    $this->data = json_decode($this->rawBody, true) ?? [];
                } else {
                    $this->data = $_POST;
                }
                break;
            case 'get':
                $this->data = $_GET;
                break;
            case 'options':
            case 'head':
            case 'purge':
            case 'trace':
                $this->data = [];
                break;
        }
    }

    public function verifySignature(string $secret, string $headerName = 'x-signature'): bool
    {
        $receivedSignature = $this->header($headerName);

        if (empty($receivedSignature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $this->rawBody, $secret);

        return hash_equals($expectedSignature, $receivedSignature);
    }

    public function raw()
    {
        return $this->rawBody;
    }
 
    protected function setFiles()
    {
        foreach ($_FILES as $key => $value) {
            $this->files[$key] = $value;
        }
    }
    
    public function base()
    {
        return $this->base;
    }
 
    public function uri()
    {
        return $this->uri;
    }

    public function host(): string
    {
        return $this->host;
    }
 
    public function method()
    {
        return $this->method;
    }

    public function contentType()
    {
        return $this->contentType;
    }

    public function all()
    {
        return $this->data;
    }
 
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }
 
    public function __get($key)
    {
        if(isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
 
    public function hasFile($key)
    {
        return isset($this->files[$key]);
    }
    
    public function file($key)
    {  
        if(isset($this->files[$key])) {
            return $this->files[$key];
        }
        return null;
    }

    public function allFiles()
    {
        return $this->files;
    }

    public function header(string $key, $default = null)
    {
        // normaliza key
        $key = strtolower(str_replace('_', '-', $key));

        // Remove o prefixo "http-" se ele foi incluído na string de busca
        if (strpos($key, 'http-') === 0) {
            $key = substr($key, 5);
        }

        return $this->headers[$key] ?? $default;
    }

    public function origin(): ?string
    {
        return $this->headers['Origin'] ?? null;
    }

    protected function getAllHeaders(): array
    {
        if (function_exists('getallheaders')) {
            // Converte as chaves do array nativo para minúsculas
            return array_change_key_case(getallheaders(), CASE_LOWER);
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace('_', '-', strtolower(substr($name, 5)));
                $headers[$key] = $value;
            } elseif (in_array($name, ["CONTENT_TYPE", "CONTENT_LENGTH", "ORIGIN"])) {
                $key = str_replace('_', '-', strtolower($name));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}
