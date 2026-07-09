<?php

return function(\Router\Router $router)
{
    if(DEBUG_MODE) {
        $router->get('/teste', 'Teste@showTeste');

        $router->get('/teste2', 'Teste@showTeste2');

        $router->get('/info2', 'Teste@showInfoPage');
    }
};
