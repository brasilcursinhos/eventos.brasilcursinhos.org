<?php
namespace App\Middleware;

use App\Service\ErrorService;
use Router\Response;
use App\Util\Auth;
use App\Util\Jwt;
use App\Util\Session;
use Router\Request;

class RoleMiddleware
{
    /**
     * Verifica as permissões.
     * Retorna um objeto Response se o acesso for negado/redirecionado.
     * Retorna null se o acesso for permitido.
     */
    public static function handle(array $allowedRoles, Request $request): ?Response
    {
        if (empty($allowedRoles)) {
            return null;
        }

        $isApi = strpos($request->uri(), '/api/') === 0;
        $isApp = strpos($request->uri(), '/app/') === 0;

        if($isApi || $isApp) {
            
            $authHeader = $request->header('authorization');
            
            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return Response::json(['error' => 'Token não fornecido ou formato inválido'], '*', 401);
            }

            $token = $matches[1];
            $payload = Jwt::decode($token);

            if (!$payload || !isset($payload['roles'])) {
                return Response::json(['error' => 'Token inválido ou expirado'], '*', 401);
            }

            $userRoles = $payload['roles'];

        } else {
            if (!Auth::isLoggedIn()) {

                if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    return Response::json(['error' => 'Login required'], '*', 401);
                }
                
                Session::flash('redirectLoginUri', $request->uri());
                return Response::redirect('/login', 303);
            }

            if (!Auth::hasRole($allowedRoles)) {
                $error = ErrorService::getErrorHttpDescription(403);
                return Response::html('error-http.html', $error, 403);
            }
        }

        return null;
    }
}