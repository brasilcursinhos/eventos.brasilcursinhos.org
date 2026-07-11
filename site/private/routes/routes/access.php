<?php

return function(\Router\Router $router)
{
    $router->get('/login', 'AccessController@showLoginPage'); 

    $router->post('/login', 'AccessController@makeLogin');

    $router->post('/logout', 'AccessController@makeLogout');

    $router->get('/recuperar-conta', 'AccessController@showRecoverAccountPage');

    $router->post('/recuperar-conta', 'AccessController@sendVerificationEmail');

    $router->get('/redefinir-senha/{code}', 'AccessController@showResetPasswordPage');

    $router->post('/redefinir-senha', 'AccessController@confirmResetPassword');

    $router->get('/redefinir-senha', 'AccessController@showResetPasswordResultPage');

    $router->post('/cadastro', 'AccessController@checkRegistrationExistence');

    $router->get('/cadastrar', 'AccessController@showSubscriptionForm');

    $router->post('/cadastrar', 'AccessController@saveRegistration');

    $router->get('/cadastrar/sucesso', 'AccessController@showSuccessRegistrationPage');
};
