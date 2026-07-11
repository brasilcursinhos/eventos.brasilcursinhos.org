<?php 
namespace App\Controller;

use Router\Response;
use App\Service\ErrorService;
use App\Repository\PublicPagesRepository;
use App\Service\PublicPagesService;
use App\Util\Session;
use Router\Request;

class PublicPagesController {

    private PublicPagesRepository $repository;
    private PublicPagesService $service;

    public function __construct(
        PublicPagesRepository $repository,
        PublicPagesService $service, 
    ) {
        $this->repository = $repository;
        $this->service = $service;
    }
    
    public function showHomePage(): Response
    {   
        return Response::html('@public/homepage.html');
    }

    public function showErrorPage(string $code): Response
    {
        $params = ErrorService::getErrorHttpDescription($code);
        return Response::html('error-http.html', $params);
    }

    public function showPrivacyPolicyPage(): Response
    {   
        return Response::html('@public/privacy-policy.html');
    }

    public function showServiceTermsPage(): Response
    {   
        return Response::html('@public/service-terms.html');
    }

    public function showAppPrivacyPolicyPage(): Response
    {   
        return Response::html('@public/app-privacy-policy.html');
    }

    public function showDonationsPage(): Response
    {
        return Response::html('@public/donations.html');
    }

    public function showHallOfFamePage(): Response
    {
        return Response::html('@public/hall-of-fame.html');
    }

    public function showWhoWeArePage(): Response
    {
        return Response::html('@public/who-we-are.html');
    }

    public function showOurTeamPage(): Response
    {
        return Response::html('@public/our-team.html');
    }

    public function showOurStoryPage(): Response
    {
        return Response::html('@public/our-story.html');
    }

    public function showAboutUsPage(): Response
    {
        $links = [
            0 => ['name' => 'Quem somos', 'url' => '/sobre-nos/quem-somos'],
            1 => ['name' => 'Nossa Equipe', 'url' => '/sobre-nos/nossa-equipe'],
            2 => ['name' => 'Nossa História', 'url' => '/sobre-nos/nossa-historia'],
            3 => ['name' => 'Hall da Fama', 'url' => '/sobre-nos/hall-da-fama'],
            4 => ['name' => 'Transparência', 'url' => '/sobre-nos/transparencia'],
            5 => ['name' => 'Documentos Institucionais', 'url' => '/sobre-nos/documentos-institucionais']
        ];
        return Response::html("subpage-links.html", ['title' => 'Sobre nós', 'links' => $links]);
    }

    public function showTransparencyPage(): Response
    {
        return Response::html('@public/transparency.html');
    }

    public function showInstitutionalDocumentsPage(): Response
    {
        return Response::html('@public/institucional-documents.html');
    }

    public function showEncupPage(): Response
    {
        return Response::html('@public/encup.html');
    }

    public function showContactPage(): Response
    {
        $success = Session::getFlash('statusContactPage');
        $errors = Session::getFlash('errorsContactPage');
        $inputs = Session::getFlash('inputsContactPage');
        
        return Response::html('@public/contact.html', [
            'success' => $success,
            'errors' => $errors,
            'inputs' => $inputs
        ]);
    }

    public function sendContactEmail(Request $request): Response
    {
        $result = $this->service->sendContactEmail($request);

        if($result->isSuccess) {
            Session::flash('statusContactPage', true);
        } else {
            Session::flash('statusContactPage', false);
            Session::flash('errorsContactPage', $result->errors);
            Session::flash('inputsContactPage', $request->all());
        }

        return Response::redirect('/contato', 303);
    }

    public function cspReport(Request $request): Response
    {
        $this->service->cspReport($request);

        return Response::empty(204);
    }

    public function getCep(string $cep): Response
    {
        $cep = preg_replace('/\D/', '', $cep);

        if(strlen($cep) === 8) {
            $ch = curl_init("https://viacep.com.br/ws/{$cep}/json/");

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $data = ['error' => true];
            } else {
                $data = json_decode($response, true);
            }
        } else {
            $data = ['error' => true];
        }
        
        return Response::json($data);
    }
}