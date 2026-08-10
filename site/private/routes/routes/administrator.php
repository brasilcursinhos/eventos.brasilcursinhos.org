<?php

use App\Enum\Role\UserRole;

return function(\Router\Router $router)
{
    $router->get([
        'set' => '/administrador',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showHomePage');

    $router->get([
        'set' => '/info',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showInfoPage');

    $router->get([
        'set' => '/administrador/encup/',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showEncupPage');

    $router->get([
        'set' => '/administrador/conciliacao',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showInfoPage');

    $router->post([
        'set' => '/administrador/encup/inscricao',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showRegistrationPage');

    $router->post([
        'set' => '/administrador/encup/pagamento',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showPaymentPage');

    $router->post([
        'set' => '/administrador/cadastro',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AccessController@checkRegistrationExistence');

    $router->get([
        'set' => '/administrador/pagamento-lote/{amount}',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showBatchPaymentPage');

    $router->post([
        'set' => '/administrador/pagamento-lote/{amount}',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@saveBatchPayment');

    $router->get([
        'set' => '/administrador/transacoes',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showTransactionsPage');

    $router->get([
        'set' => '/administrador/conciliar-transacoes',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@reconcileTransactions');
};
