<?php

    $container = require_once __DIR__."/../private/bootstrap.php";

    $request = new \Router\Request;

    if (!$request->isSecure()) {
        $url = 'https://' . $request->host() . $request->base();
        $response = \Router\Response::redirect($url, 301);
        $response->send();
        exit();
    }
    
    $container->set(\Router\Request::class, $request);

    $router = new \Router\Router($container);

    $routeDefinition = require_once __DIR__ . '/../private/routes/routes.php';

    $routeDefinition($router);

    try {

        $response = $router->resolve($request);

    } catch (\Router\RouteNotFoundException $exception) {
        $uri = $request->uri();
        \App\Util\Log::error("Erro 404 na URL: /$uri", 'not-found.log', $exception->getMessage());
        $data = [
            'code' => '404',
            'name' => "Página não encontrada",
            'description' => "A página solicitada não foi encontrada no servidor."
        ];
        $response = \Router\Response::html('error-http.html', $data, 404)->withoutCache();

    } catch (\Router\Exception\RouteNotFound $exception) {
        $uri = $request->uri();
        \App\Util\Log::error("Erro 404 na URL: /$uri", 'not-found.log', $exception->getMessage());
        $data = [
            'code' => '404',
            'name' => "Página não encontrada",
            'description' => "A página solicitada não foi encontrada no servidor."
        ];
        $response = \Router\Response::html('error-http.html', $data, 404)->withoutCache();

    } catch (\Router\Exception\RouteForbidden $exception) {
        $uri = $request->uri();
        \App\Util\Log::error("Erro 403 na URL: /$uri", 'forbidden.log', $exception->getMessage());
        $data = [
            'code' => '403',
            'name' => "Acesso proibido",
            'description' => "Você não tem permissão para acessar essa página."
        ];
        $response = \Router\Response::html('error-http.html', $data, 403)->withoutCache();

    } catch (\Exception $exception) {

        \App\Util\Log::error('Erro ao renderizar site', 'error.log', $exception->getMessage());
        $data = [
            'code' => '500',
            'name' => 'Erro interno do servidor'
        ];
        $response = \Router\Response::html('error-http.html', $data, 500)->withoutCache();
    }

    $response->send();
