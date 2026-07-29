<?php

use App\Enum\Role\UserRole;

return function(\Router\Router $router)
{
    $router->get([
        'set' => '/participante',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@showHomePage');

    $router->get([
        'set' => '/participante/encup',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@checkEncupRegistrationStatus');

    $router->get([
        'set' => '/participante/encup/inscricao',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@showEncupRegistrationPage');

    $router->get([
        'set' => '/participante/encup/inscricao/{code}',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@showEncupRegistrationPage');

    $router->post([
        'set' => '/participante/encup/inscricao',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@saveEncupRegistration');

    $router->get([
        'set' => '/participante/encup/pagamento',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@showEventPaymentPage');

    $router->post([
        'set' => '/participante/encup/pagamento',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@savePaymentProof');

    $router->get([
        'set' => '/participante/encup/status',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@showEventStatusPage');
};