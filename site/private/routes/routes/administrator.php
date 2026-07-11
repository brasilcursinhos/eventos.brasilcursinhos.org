<?php

use App\Enum\Role\UserRole;

return function(\Router\Router $router)
{
    $router->get([
        'set' => '/administrador',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showHomePage');

    $router->get([
        'set' => '/administrador/entrevistas',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showInterviewsPage');

    $router->get([
        'set' => '/administrador/agendamentos',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showSchedulesPage');

    $router->post([
        'set' => '/administrador/entrevista/confirmar',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@confirmInterview');

    $router->post([
        'set' => '/administrador/entrevista/salvar',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@saveInterview');

    $router->get([
        'set' => '/administrador/insert',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@insertUsers');

    $router->get([
        'set' => '/administrador/insert-students',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@insertStudents');

    $router->get([
        'set' => '/administrador/entrevistas/{room}',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@getInterviews');

    $router->get([
        'set' => '/administrador/candidatos/{type}',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@getMemberCandidates');

    $router->get([
        'set' => '/administrador/membros/',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showMembersPage');

    $router->post([
        'set' => '/administrador/membro',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showMemberRegister');

    $router->get([
        'set' => '/administrador/membro',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showMemberRegister');

    $router->get([
        'set' => '/administrador/alunos/',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showStudentsPage');

    $router->post([
        'set' => '/administrador/aluno',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showStudentRegister');

    $router->get([
        'set' => '/administrador/aluno',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showStudentRegister');

    $router->get([
        'set' => '/info',
        'roles' => [UserRole::ADMINSTRATOR]
    ], 'AdministratorController@showInfoPage');
};
