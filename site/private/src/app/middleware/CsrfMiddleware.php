<?php
namespace App\Middleware;

use Router\Request;
use Router\Response;
use App\Util\CsrfToken;

class CsrfMiddleware
{
    public static function handle(Request $request): ?Response
    {

        $uri = $request->uri();

        if (strpos($uri, '/api/') === 0 || strpos($uri, '/app/') === 0) {
            return null;
        }

        $method = $request->method();

        if (in_array($method, ['get', 'options', 'head', 'trace'])) {
            return null;
        }

        $token = $request->__get('csrf-token') ?? $request->header('x-csrf-token');

        if (!CsrfToken::verify($token)) {
            return Response::html('error-http.html', [
                'code' => '419',
                'name' => 'Página Expirada',
                'description' => 'Sua sessão expirou por inatividade. Por favor, atualize a página e tente novamente.'
            ], 419);
        }

        return null;
    }
}