<?php 
namespace App\Controller;

use Router\Response;
use App\Util\Auth;

class ParticipantController
{
    public function showHomePage(): Response
    {
        return Response::html('@participant/home.html', ['user' => Auth::user()]);
    }
}