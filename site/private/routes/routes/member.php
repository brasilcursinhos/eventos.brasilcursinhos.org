<?php

use App\Enum\Role\UserRole;

return function(\Router\Router $router)
{
    $router->get([
        'set' => '/membro',
        'roles' => UserRole::members()
    ], 'MemberController@showHomePage');

    $router->get([
        'set' => '/membro/update-transacoes',
        'roles' => UserRole::members()
    ], 'MemberController@showUpdateTransactionsPage');

    $router->post([
        'set' => '/membro/update-transacoes',
        'roles' => UserRole::members()
    ], 'MemberController@updateTransactions');
};