<?php

use App\Authenticator;

function isLoggedIn(): bool
{
    return Authenticator::checkLogin();
}