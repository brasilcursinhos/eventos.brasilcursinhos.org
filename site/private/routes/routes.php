<?php
return function(\Router\Router $router)
{
    (require __DIR__ . '/routes/public.php')($router);
    (require __DIR__ . '/routes/access.php')($router);
    (require __DIR__ . '/routes/administrator.php')($router);
    (require __DIR__ . '/routes/participant.php')($router);
    (require __DIR__ . '/routes/app.php')($router);
    (require __DIR__ . '/routes/redirects.php')($router);
};

