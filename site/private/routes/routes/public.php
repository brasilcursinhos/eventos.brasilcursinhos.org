<?php

return function(\Router\Router $router)
{
    $router->get('/', 'PublicPagesController@showHomePage');

    $router->get('/erro/{code}', 'PublicPagesController@showErrorPage');

    $router->get('/politica-de-privacidade', 'PublicPagesController@showPrivacyPolicyPage');

    $router->get('/app/politica-de-privacidade', 'PublicPagesController@showAppPrivacyPolicyPage');

    $router->get('/encup', 'PublicPagesController@showEncupPage');

    $router->get('/contato', 'PublicPagesController@showContactPage');

    $router->post('/contato', 'PublicPagesController@sendContactEmail');

    $router->post('/csp-reports', 'PublicPagesController@cspReport');

    $router->get('/cep/{cep}', 'PublicPagesController@getCep');
};
