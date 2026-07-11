<?php

use App\Enum\Role\UserRole;

return function(\Router\Router $router)
{
    $router->get([
        'set' => '/participante',
        'roles' => [UserRole::EVENT_PARTICIPANT]
    ], 'ParticipantController@showHomePage');
};