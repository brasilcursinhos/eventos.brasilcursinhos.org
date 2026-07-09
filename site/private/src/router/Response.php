<?php
namespace Router;

use App\Util\Session;
use App\Util\Viewer;

class Response
{
    private ?string $content;
    private int $statusCode;
    private array $headers;
    private array $csp = [];
    private ?array $streamData = null;
    private ?string $streamType = null;

    private array $defaultHeaders = [
        // Cabeçalhos movidos para o .htaccess global
        //'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
        //'X-Content-Type-Options' => 'nosniff',
        //'Referrer-Policy' => 'strict-origin-when-cross-origin',
        //'X-Frame-Options' => 'DENY',
        //'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), interest-cohort=()',
        //'Cross-Origin-Embedder-Policy' => 'require-corp',
        //'Cross-Origin-Opener-Policy' => 'same-origin',
        //'Cross-Origin-Resource-Policy' => 'same-origin',
        //'X-Permitted-Cross-Domain-Policies' => 'none'
        'Report-To' => '{"group":"csp-endpoint","max_age":10886400,"endpoints":[{"url":"/csp-reports"}]}',
    ];

    protected function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_merge($this->defaultHeaders, $headers);
        $this->csp = [
            'default-src' => ["'none'"],
            'script-src' => [
                "'self'"
            ],
            'script-src-elem' => [
                "'self'", 
                'https://www.googletagmanager.com', 
                'https://www.google-analytics.com',
                'https://viacep.com.br'
            ],
            'style-src' => [
                "'self'"
            ],
            'style-src-elem' => [
                "'self'"
            ],
            'font-src' => [
                "'self'"
            ],
            'img-src' => [
                "'self'", 
                'https://www.google-analytics.com', 
                'https://*.googletagmanager.com',
                'https://quickchart.io',
                'data:'
            ],
            'connect-src' => [
                "'self'", 
                'https://www.google-analytics.com', 
                'https://*.googletagmanager.com'
            ],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'none'"],
            'frame-src' => ["'none'"],
            'upgrade-insecure-requests' => [],
            'report-to' => ['csp-endpoint'],
            'report-uri' => ['/csp-reports']
        ];
    }

    public function setCSPDirective(string $directive, array $sources): self
    {
        $this->csp[$directive] = $sources;
        return $this;
    }

    public function withoutCSP(): self
    {
        $this->csp = [];
        return $this;
    }

    private function buildCSPString(): string
    {
        $policyParts = [];
        foreach ($this->csp as $directive => $values) {
            // Se o array de valores estiver vazio, é uma diretiva "flag"
            if (empty($values)) {
                $policyParts[] = $directive;
            } else {
                // Junta as fontes com espaço
                $policyParts[] = $directive . ' ' . implode(' ', $values);
            }
        }
        // Junta as diretivas com ponto e vírgula
        return implode('; ', $policyParts);
    }

    public function withCache(int $maxAge = 3600): self
    {
        $this->headers['Cache-Control'] = "public, max-age=$maxAge";
        $this->headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT';
        unset($this->headers['Pragma']);
        return $this;
    }

    public function withoutCache(): self
    {
        $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $this->headers['Pragma'] = 'no-cache';
        unset($this->headers['Expires']);
        return $this;
    }

    public static function html(string $template, array $vars = [], int $status = 200, array $headers = []): self
    {
        $content = Viewer::render($template, $vars);
        $defaultHeaders = ['Content-Type' => 'text/html; charset=utf-8'];
        
        $response = new static($content, $status, array_merge($defaultHeaders, $headers));
        
        return $response->withCache(3600);
    }

    public static function json(mixed $data, string $allowOrigin = '*', int $status = 200, array $headers = []): self
    {
        $content = json_encode($data);

        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin' => $allowOrigin
        ];
        
        $response = new static($content, $status, array_merge($defaultHeaders, $headers));

        return $response->withoutCache();
    }

    public static function redirect(string $url, int $status = 302): self
    {
        Session::save();
        $response = new static('', $status);
        $response->headers = ['Location' => $url];
        $response->csp = [];
        return $response->withoutCache();
    }

    public static function csv(array $data, string $filename = 'export.csv', array $headers = []): self
    {
        $defaultHeaders = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        $response = new static('', 200, array_merge($defaultHeaders, $headers));
        
        $response->streamType = 'csv';
        $response->streamData = $data;
        
        return $response->withoutCache();
    }

    /*public static function file(string $content, string $filename, string $contentType, int $status = 200, array $headers = []): self
    {
        $defaultHeaders = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        $response = new static($content, $status, array_merge($defaultHeaders, $headers));
        return $response->withoutCache();
    }*/
    public static function file(string $content, string $filename, string $contentType, bool $download = false, int $status = 200, array $headers = []): self
    {
        $disposition = $download ? 'attachment' : 'inline';

        $defaultHeaders = [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($content)
        ];
        
        $response = new static($content, $status, array_merge($defaultHeaders, $headers));
        
        return $response->withoutCache();
    }

    public static function cors(string $allowOrigin = '*', int $status = 204, array $headers = []): self
    {
        $defaultHeaders = [
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Accept, Origin, X-App-Key',
            'Access-Control-Max-Age' => '86400',
            'Access-Control-Allow-Origin' => $allowOrigin
        ];
        return new static('', $status, array_merge($defaultHeaders, $headers));
    }

    public static function empty(int $status = 204, array $headers = []): self
    {
        return new static('', $status, $headers);
    }

    public static function error(int $status = 404, array $headers = []): self
    {
        $response = new static('', $status, $headers);
        return $response->withoutCache();
    }

    public function send(): void
    {
        header_remove('X-Powered-By');
        if (!isset($this->headers['Pragma'])) {
            header_remove('Pragma');
        }
        http_response_code($this->statusCode);
        
        if(!empty($this->csp)) {
            $this->headers['Content-Security-Policy'] = $this->buildCSPString();
        }
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if(!isset($this->headers['Location'])) {
            $canonicalUrl = 'https://pes.ufsc.br' . ((empty($_SERVER['REDIRECT_URL'])) ? '/' : rtrim($_SERVER['REDIRECT_URL'], '/'));
            header('Link: <' . $canonicalUrl . '>; rel="canonical"');
        }
        
        if ($this->streamType !== 'csv') {
            if(!empty($this->content)) {
                echo $this->content;
            }
        } else {
            $this->sendCsvStream();
        }
    }

    private function sendCsvStream(): void
    {
        $output = fopen('php://output', 'w');
        
        if (empty($this->streamData)) {
            fclose($output);
            return;
        }

        fwrite($output, "\xEF\xBB\xBF");

        $first = is_array($this->streamData) ? reset($this->streamData) : $this->streamData;

        if (is_object($first)) {
            $headers = array_keys((array)$first);
        } elseif (is_array($first)) {
            $headers = array_keys($first);
        } else {
            throw new \InvalidArgumentException("Formato de dado inválido (esperado array ou objeto).");
        }

        fputcsv($output, $headers, ';', '"', '\\');

        foreach ($this->streamData as $row) {
            $rowArray = is_object($row) ? (array)$row : $row;
            fputcsv($output, $rowArray, ';', '"', '\\');
        }

        fclose($output);
    }
}
