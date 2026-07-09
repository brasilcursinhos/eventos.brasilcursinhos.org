<?php 
namespace App\Controller;

use Router\Response;

class AppController
{
    public function cors(): Response
    {
        return Response::cors();
    }

    public function login(): Response
    {
        return Response::empty();
    }

    public function saveStudentPresence(): Response
    {
        return Response::empty();
    }

    public function saveStudentAdvertence(): Response
    {
        return Response::empty();
    }

}