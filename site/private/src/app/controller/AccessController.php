<?php 
namespace App\Controller;

use App\Enum\Result\AuthResult;
use App\Enum\Status\SystemStatus;
use App\Enum\Status\UserStatus;
use App\Repository\AccessRepository;
use App\Service\ErrorService;
use App\Service\ValidatorService;
use Router\Response;
use App\Util\Auth;
use App\Util\Email;
use App\Util\Session;
use MongoDB\BSON\ObjectId;
use Router\Request;

class AccessController {

    private AccessRepository $repository;

    public function __construct(AccessRepository $repository)
    {
        $this->repository = $repository;
    }

    public function showLoginPage(): Response
    {
        if(!Auth::isLoggedIn()) {
            $errorMessage = Session::getFlash('errorMessage');
            return Response::html('@access/login.html', ['errorMessage' => $errorMessage]);
        } else {
            $user = Auth::user();
            return Response::redirect($user->type->url(), 303);
        }
    }

    public function makeLogin(Request $request): Response
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));
        $password = $request->__get('password');

        if($cpf) {
            $result = Auth::login($cpf, $password, $this->repository);
            if($result === AuthResult::SUCCESS) {
                $url = Session::getFlash('redirectLoginUri') ?? Auth::user()->type->url();
                return Response::redirect($url, 303);
            } else {
                Session::flash('errorMessage', $result->message());
            }
        } else {
            Session::flash('errorMessage', 'O CPF informado é inválido!');
        }

        return Response::redirect('/login', 303);
    }

    public function makeLogout(): Response
    {
        Auth::logout();
        return Response::redirect('/login', 303);
    }

    public function showRecoverAccountPage()
    {
        $errorMessage = Session::getFlash('errorMessage');
        $email = Session::getFlash('email');
        return Response::html('@access/recover-account.html', ['errorMessage' => $errorMessage, 'email' => $email])->withoutCache();
    }

    public function sendVerificationEmail(Request $request)
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf'));

        if(!$cpf) {
            Session::flash('errorMessage', "O CPF informado é inválido!");
        } else {
            $user = $this->repository->getUser($cpf);
            if(!$user) {
                Session::flash('errorMessage', "Nenhum usuário encontrado para o CPF informado!");
            } else {
                $status = UserStatus::tryFrom($user->status);
                if(!is_null($status) && $status->isChangeablePassword()) {
                    $code = Auth::getRandomCode(64);
                    if($this->repository->insertVerificationCode($code, $user->id)) {

                        try {

                            $email = Email::create();
                            
                            $email->subject('Recuperação de conta - Cursinho PES');
                            $email->replyTo('suporte@pes.ufsc.br', 'Suporte - Cursinho PES');
                            $email->to($user->email, $user->nickname);
                            $email->renderBody('recover-account.html', [
                                'name' => $user->nickname,
                                'code' => $code
                            ]);
                            
                            if(!$email->send()) {
                                Session::flash('errorMessage', "Erro ao enviar e-mail de confirmação!");
                            }
                        } catch(\Exception $exception) {
                            Session::flash('errorMessage', "Erro ao enviar e-mail de confirmação!");
                        }

                        Session::flash('email', $user->email);

                    } else {
                        Session::flash('errorMessage', "Erro ao gravar o código de verificação no banco de dados!");
                    }
                } else {
                    Session::flash('errorMessage', "O status atual do usuário não permite alteração de senha.");
                }
            }
        }
        
        return Response::redirect('/recuperar-conta', 303);
    }

    public function showResetPasswordPage(string $code)
    {
        $user = $this->repository->confirmVerificationCode($code);
        if($user) {
            $data = ['name' => $user->nickname, 'verificationCode' => $code, 'cpf' => $user->cpf];
        } else {
            $data = [];
        }
    
        return Response::html('@access/reset-password.html', $data)->withoutCache();
    }

    public function confirmResetPassword(Request $request)
    {
        $password = ValidatorService::validatePassword($request->__get('password'));
        $passwordConfirmation = ValidatorService::validatePassword($request->__get('password-confirmation'));

        if($password !== false && $password === $passwordConfirmation) {
            $code = $request->__get('verification-code');
            $hash = Auth::passwordHash($password);
            if($this->repository->insertNewPasswordHash($code, $hash)) {
                Session::flash('success', true);
            } else {
                Session::flash('errorMessage', 'Ocorreu um erro no banco de dados.');
            }
        } else {
            Session::flash('errorMessage', 'Ocorreu um erro ao validar a sua senha.');
        }
        
        return Response::redirect('/redefinir-senha', 303);
    }

    public function showResetPasswordResultPage(): Response
    {
        $success = Session::getFlash('success');
        $errorMessage = Session::getFlash('errorMessage');

        if(is_null($success) && is_null($errorMessage)) {
            throw new \Router\RouteNotFoundException();
        }

        return Response::html('@access/reset-password-result.html', ['success' => $success, 'errorMessage' => $errorMessage]);
    }

    public function showSubscriptionPage(): Response
    {
        $success = Session::getFlash('success');
        $errorMessage = Session::getFlash('errorMessage');
        
        return Response::html('@access/subscription.html', ['success' => $success, 'errorMessage' => $errorMessage]);
    }

    public function checkRegistrationExistence(Request $request): Response 
    {
        $cpf = ValidatorService::validateCpf($request->__get('cpf-registration'));
        $email = ValidatorService::validateEmail($request->__get('email'));
        $email2 = ValidatorService::validateEmail($request->__get('email2'));

        if($cpf && $email && $email2 && $email === $email2) {

            if($this->repository->isRegistred($cpf)) {
                return Response::html('@access/errors/registration-existence.html');
            }

            Session::set('cpf', $cpf);
            Session::set('email', $email);

            return Response::redirect("/cadastrar", 303);

        } else {
            throw new \UnexpectedValueException();
        }
    }

    public function showSubscriptionForm(): Response
    {
        $cpf = Session::get('cpf');
        $email = Session::get('email');

        if(is_null($cpf) || is_null($email)) {
            $description = ErrorService::getErrorHttpDescription(403);
            return Response::html('error-http.html', $description);
        }

        return Response::html('@access/subscription.html', [
            'cpf' => $cpf,
            'email' => $email
        ]);
    }

    public function saveRegistration(Request $request, ValidatorService $validator): Response
    {
        Session::delete('cpf');
        Session::delete('email');

        try {
            $personalData = $validator->validatePersonalData($request->all());
        } catch (\Exception $exception) {
            throw new \UnexpectedValueException();
        }

        try {
            $userId = $this->repository->saveRegistrationn($personalData);
        } catch (\Exception $exception) {
            throw new \UnexpectedValueException();
        }

        $code = Auth::getRandomCode(64);
        if($this->repository->insertVerificationCode($code, $userId)) {

            try {

                $email = Email::create();
                
                $email->subject('Recuperação de conta - Cursinho PES');
                $email->replyTo('suporte@brasilcursinhos.org', 'Suporte - rasil Cursinhos');
                $email->to($personalData->email, $personalData->nickname);
                $email->renderBody('recover-account.html', [
                    'name' => $personalData->nickname,
                    'code' => $code
                ]);
                
                if(!$email->send()) {
                    Session::flash('errorMessage', "Erro ao enviar e-mail de confirmação!");
                }
            } catch(\Exception $exception) {
                Session::flash('errorMessage', "Erro ao enviar e-mail de confirmação!");
            }

            Session::flash('email', $personalData->email);

        } else {
            Session::flash('errorMessage', "Erro ao gravar o código de verificação no banco de dados!");
        }

        return Response::redirect("/cadastrar/sucesso", 303);
    }

    public function showSuccessRegistrationPage(): Response
    {
        return Response::html('@access/success.html');
    }
    
    // exibe a página de login
    /*public function showLoginPage($error = [])
    {
        if(!Authenticator::checkLogin()){
            
            Page::render('@public/login.html', $error);

        }else{
            
            $url = Authenticator::getUserURL();
            
            if($url === "401"){
                Page::showErrorHttpPage($url);
            } else {
                header("Location: $url");
            }
        }
    }

    // checa se os dados de login estão corretos e redireciona para a 
    // página de usuário específica a depender do perfil do usuário logado
    public function checkLogin()
    {   
        $request = new Request();
        
        $user = $request->__get("user");
        $password = $request->__get("password");
        
        $callback = Authenticator::makeLogin($user, $password);
        
        if($callback['error'] === false) {
            
            $url = Authenticator::getUserURL();
            
            if($url === "401"){
                Page::showErrorHttpPage($url);
            } else {
                header("Location: $url");
                exit();
            }
            
        } else {
            if($callback['code'] === Authenticator::ERROR_BLOCKED_USER) {
                echo 'Usuário bloqueado<br><br><a href="/login">login</a>';
            } else if($callback['code'] === Authenticator::ERROR_INACTIVE_USER) {
                echo 'Usuário inativo<br><br><a href="/login">login</a>';
            } else if($callback['code'] === Authenticator::ERROR_DISABLED_USER) {
                echo 'Usuário desligado<br><br><a href="/login">login</a>';
            } else {
                $this->showLoginPage($callback);
            }
        }
    }

    public function makeLogout()
    {
        Authenticator::makeLogout();
        header("Location: /login");
        exit();
    }

    //exibe a página para recuperação de senha
    public function showRecoverPasswordPage($error = [])
    {
        Page::showUnderConstructionPage('Recuperação de Senha');
        exit;
        Page::render('@public/recover-password.html', $error);
        exit();
    }

    public function sendEmailVerificationCode()
    {
        exit;
        $request = new Request();

        $cpf = $request->__get('cpf');

        Authenticator::sendEmailVerificationCode($cpf);

        if($cpf) {

        } else {

        }
    }

    // realiza a validação do código de email
    public function showValidationPage($user, $code)
    {
        Page::showUnderConstructionPage("Recuperação de Acesso");
        exit;
        echo "Validação de e-mail <br>";
        echo "user: ".$user."   |   code: ".$code;
        echo "<br><a href='/'>Voltar ao início</a>";
    }

    // verifica pré a existencia de cadastro no banco em algum formulário com base no item consultado
    public function checkCpfRegistration($form, $code)
    {
        Page::showUnderConstructionPage("Login");
        exit;
        $request = new Request();
        $data = trim(preg_replace('/[^0-9]/', '', $request->__get('cpf')));
        $cpf = DataValidator::validateCpf($data);
        if($cpf === false){
            echo "false";
            exit();
        }
        
        if($form === 'otep'){
            if(Authenticator::checkAcessValicationCode($code, 'otep') === true) {
                $return = (SelectDB::thereIsRegistrationCpf($cpf, 'OTEP') === false)? "true":"false";
            } else {
                http_response_code(401);
                exit();
            }
            
        } else {
            $return = 'false';
        }

        echo $return;

        exit();
    }*/
}