<?php 
namespace App\Controller;

use App\Enum\UserRole;
use App\Repository\MembersRepository;
use App\Repository\SelectionProcessesRepository;
use App\Repository\StudentsRepository;
use App\Service\ValidatorService;
use App\Util\Auth;
use App\Util\Session;
use Router\Request;
use Router\Response;

class AdministratorController
{
    private $links;

    public function __construct()
    {
        $this->links =  array(
            (object) array('name' => 'Página inicial', 'url' => '/administrador'),
            (object) array('name' => 'Conferir/Atualizar Cadastro', 'url' => '/administrador/entrevistas'),
            (object) array('name' => 'Conciliação de pagamentos', 'url' => '/administrador/agendamentos'),
            (object) array('name' => 'Lista de Participantes', 'url' => '/administrador/membros')
        );


        if(Auth::hasRole(UserRole::members())) {
            array_push(
                $this->links,
                ...[
                    (object) array('name' => 'Ir para página de Participante', 'url' => '/participante')
                ]
            );
        }
    }

    public function showInfoPage()
    {
        ob_start();
        phpinfo();
        $html_completo = ob_get_clean();

        $dom = new \DOMDocument();
        // O prefixo abaixo evita problemas com caracteres especiais/acentuação
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_completo);

        // Captura o CSS original (sem alterações)
        $style_tags = $dom->getElementsByTagName('style');
        $css_original = ($style_tags->length > 0) ? $style_tags->item(0)->textContent : "";

        // Captura o conteúdo do Body
        $body = $dom->getElementsByTagName('body')->item(0);
        $body_inner = "";
        foreach ($body->childNodes as $child) {
            $body_inner .= $dom->saveHTML($child);
        }

        return Response::html('@admin/php-info.html', ['links' => $this->links, 'css' => $css_original, 'content' => $body_inner])->withoutCSP();
    }
    
    public function showHomePage(): Response
    {
        return Response::html('@admin/home.html', ['user' => Auth::user(), 'links' => $this->links])->withoutCache();
    }

    public function showInterviewsPage(SelectionProcessesRepository $repository): Response
    {
        $data = $repository->getInterviewTimes();
        return Response::html('@admin/entrevistas.html', ['user' => Auth::user(), 'links' => $this->links, 'data' => $data])->withoutCache();
    }

    public function showSchedulesPage(SelectionProcessesRepository $repository): Response
    {
        $data = $repository->getSchedules();
        return Response::html('@admin/agendamentos.html', ['user' => Auth::user(), 'links' => $this->links, 'data' => $data])->withoutCache();
    }

    public function confirmInterview(Request $request, SelectionProcessesRepository $repository)
    {
        
        $registration = ValidatorService::validateRegistrationNumber($request->__get('registration'));
        $interviewTime = ValidatorService::validateInt($request->__get('interview-time'));
        if($registration && $interviewTime) {
            $student = $repository->getStudentCandidateInfo($registration);
            $interview = $repository->getInterviewTimeInfo($interviewTime);
            if($student && $interview) {
                return Response::html('@admin/confirmar-entrevista.html', ['student' => $student, 'interview' => $interview]);
            }
            
        } else {
            echo "Erro com os dados de entrada.";
            return Response::error(400);
        }
    }

    public function saveInterview(Request $request, SelectionProcessesRepository $repository)
    {
        $student = ValidatorService::validateInt($request->__get('student'));
        $interview = ValidatorService::validateInt($request->__get('interview'));
        if($student && $interview) {
            
            $interviewSchedule = $repository->insertInterview($student, $interview);

            return Response::html('@admin/resultado-entrevista.html', ['status' => $interviewSchedule->errorStatus]);
            
        } else {
            echo "Erro com os dados de entrada.<br><br><a href='/administrador/entrevistas'>Voltar</a>";
            return Response::error(400);
        }
    }

    public function getInterviews(string $room, \PDO $pdo): Response
    {
        $sql = "SELECT pd.`idUser` AS `id`, pd.firstName, pd.lastName, pd.cpf, c.registration, it.room, DATE_FORMAT(it.datetime, '%d/%m/%Y às %Hh%i') AS `datetime` FROM INTERVIEW_TIMES it LEFT JOIN INTERVIEW_SCHEDULES isc ON it.idInterviewTime = isc.idInterviewTime LEFT JOIN PERSONAL_DATA pd ON isc.idUser = pd.idUser LEFT JOIN CANDIDATES c ON isc.idUser = c.idUser WHERE it.room = '$room-A' ORDER BY it.`datetime` ASC";
        $stmt = $pdo->query($sql);
        $usuariosCriptografados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Processar os dados e descriptografar os campos
        $dadosProcessados = [];
        foreach ($usuariosCriptografados as $linha) {
            $userADD = 'USER_ID_' . $linha['id'];
            $dadosProcessados[] = [
                'registration' => is_null($linha['registration'])? '—':\App\Util\Crypto::decrypt($linha['registration'], $userADD),
                'name'    => is_null($linha['firstName'])? '—':\App\Util\Crypto::decrypt($linha['firstName'], $userADD) . ' ' . \App\Util\Crypto::decrypt($linha['lastName'], $userADD),
                'datetime'    => $linha['datetime'],
                'cpf'          => is_null($linha['lastName'])? '—':preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", \App\Util\Crypto::decrypt($linha['cpf'] ?? 1, $userADD)),
                'room' => $linha['room']
            ];
        }

        $nomeArquivo = $room . '.csv';
        return Response::csv($dadosProcessados, $nomeArquivo);
    }

    public function insertUsers(\PDO $pdo): Response
    {
        $path = __DIR__ . '/input.csv';
        if(($file = fopen($path, 'r')) !== false) {
            $header = fgetcsv($file, separator: ";", escape: "");
            while(($row = fgetcsv($file, separator: ";", escape: "")) !== false) {
                $cpf           = $row[0];
                $firstName     = $row[1];
                $lastName      = $row[2];
                $nickname      = $row[3];
                $pronouns      = $row[4];
                $gender        = $row[5];
                $birthDate     = $row[6];
                $email         = $row[7];
                $phone         = $row[8];
                $emergencyInfo = $row[9];

                try {

                    $pdo->beginTransaction();

                    
                    $stmt = $pdo->prepare("INSERT INTO `USERS` (`cpfHash`, `type`, `status`, `createdAt`, `updatedAt`) VALUES(:cpfHash, :type_, :status_, NOW(), NOW())");
                    
                    $stmt->bindValue(':cpfHash', \App\Util\Crypto::hash($cpf), \PDO::PARAM_LOB);
                    $stmt->bindValue(':type_', \App\Enum\UserType::MEMBER->value, \PDO::PARAM_INT);
                    $stmt->bindValue(':status_', \App\Enum\UserStatus::PENDING->value, \PDO::PARAM_INT);
                    $stmt->execute();

                    $userID = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO `PERSONAL_DATA` (`firstName`, `lastName`, `socialName`, `nickname`, `pronouns`, `gender`, `cpf`, `birthDate`, `email`, `emailHash`, `phone`, `phoneHash`, `emergencyInfo`, `address`, `idUser`, `createdAt`, `updatedAt`) VALUES(:firstName, :lastName, :socialName, :nickname, :pronouns, :gender, :cpf, :birthDate, :email, :emailHash, :phone, :phoneHash, :emergencyInfo, :address_, :idUser, NOW(), NOW())");
                    $userADD = 'USER_ID_' . $userID;
                    $stmt->bindValue(':firstName', \App\Util\Crypto::encrypt($firstName, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':lastName', \App\Util\Crypto::encrypt($lastName, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':nickname', \App\Util\Crypto::encrypt($nickname, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':pronouns', \App\Util\Crypto::encrypt($pronouns, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':gender', \App\Util\Crypto::encrypt($gender, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':cpf', \App\Util\Crypto::encrypt($cpf, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':birthDate', \App\Util\Crypto::encrypt($birthDate, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':email', \App\Util\Crypto::encrypt($email, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':emailHash', \App\Util\Crypto::hash($email), \PDO::PARAM_LOB);
                    $stmt->bindValue(':phone', \App\Util\Crypto::encrypt($phone, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':phoneHash', \App\Util\Crypto::hash($phone), \PDO::PARAM_LOB);
                    $stmt->bindValue(':emergencyInfo', \App\Util\Crypto::encrypt($emergencyInfo, $userADD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':socialName', null, \PDO::PARAM_NULL);
                    $stmt->bindValue(':address_', null, \PDO::PARAM_NULL);
                    $stmt->bindValue(':idUser', $userID, \PDO::PARAM_INT);
                    $stmt->execute();

                    $stmt = $pdo->prepare("INSERT INTO `USER_ROLES` (`role`, `idUser`, `createdAt`) VALUES(:role_, :idUser, NOW())");
                    $stmt->bindValue(':role_', \App\Enum\UserRole::MANAGER->value, \PDO::PARAM_INT);
                    $stmt->bindValue(':idUser', $userID, \PDO::PARAM_INT);
                    $stmt->execute();

                    $pdo->commit();

                } catch(\Exception $excetion) {

                    $pdo->rollBack();

                    \App\Util\Log::error('Erro ao inserir usuario: ' .$cpf, 'database.log', $excetion->getMessage());

                    continue;
                }
            }
            fclose($file);
        }
        return Response::empty();
    }

    public function insertStudents(\PDO $pdo): Response
    {
        $path = __DIR__ . '/codes.csv';
        if(($file = fopen($path, 'r')) !== false) {
            $header = fgetcsv($file, separator: ";", escape: "");
            while(($row = fgetcsv($file, separator: ";", escape: "")) !== false) {
                $userId = $row[0];
                $code   = $row[1];
                $class  = $row[2];
                
                try {

                    $pdo->beginTransaction();

                    $select = $pdo->prepare('SELECT `idStudent` FROM `STUDENTS` WHERE `idUser` = :idUser');
                    $select->bindValue(':idUser', $userId, \PDO::PARAM_INT);
                    $select->execute();
                    $result = $select->fetch();
                    $idStudent = $result->idStudent;

                    $stmt = $pdo->prepare("INSERT INTO `STUDENT_ENROLLMENTS` (`idStudent`, `idClass`, `enrollmentDate`, `status`, `badgeCode`, `badgeCodeHash`, `createdAt`, `updatedAt`) VALUES (:idStudent, :idClass, :enrollmentDate, :status_, :badgeCode, :badgeCodeHash, NOW(), NOW())");
                    $studentAAD = 'STUDENT_ID_' . $idStudent;
                    $stmt->bindValue(':badgeCode', \App\Util\Crypto::encrypt($code, $studentAAD), \PDO::PARAM_LOB);
                    $stmt->bindValue(':badgeCodeHash', \App\Util\Crypto::hash($code), \PDO::PARAM_LOB);
                    $stmt->bindValue(':idStudent', $idStudent, \PDO::PARAM_INT);
                    $stmt->bindValue(':idClass', $class, \PDO::PARAM_INT);
                    $stmt->bindValue(':status_', 2, \PDO::PARAM_INT);
                    $stmt->bindValue(':enrollmentDate', '2026-03-23', \PDO::PARAM_STR);
                    $stmt->execute();

                    $pdo->commit();

                } catch(\Exception $excetion) {

                    $pdo->rollBack();

                    \App\Util\Log::error('Erro ao inserir aluno ' . $userId . 'na turma' .$class, 'database.log', $excetion->getMessage());

                    continue;
                }
            }
            fclose($file);
        }
        return Response::empty();
    }

    public function getMemberCandidates(string $type, SelectionProcessesRepository $repository): Response
    {
        if($type === 'alunos') {
            $data = $repository->getStudentsList();
        } else if($type === 'profs') {
            $data = $repository->getMemberCandidateInformation(false);
        } else {
            $data = $repository->getMemberCandidateInformation();
        }
        return Response::csv($data, 'candidatos2.csv');
    }

    public function showMembersPage(MembersRepository $repository): Response
    {
        $members = $repository->getMembersList();
        return Response::html('@admin/members.html', ['links' => $this->links, 'members' => $members])->withoutCache();
    }

    public function showMemberRegister(Request $request, ValidatorService $validator, MembersRepository $repository): Response
    {
        $userId = Session::getFlash('adminUpdateUserId') ?? $validator->validateInt($request->__get('user-id')) ?: Session::getFlash('reloadUserId');
        $error = Session::getFlash('updateRegisterError');

        if($userId) {
            Session::flash('reloadUserId', $userId);
            $member = $repository->getProfileInfo($userId);
            if($member) {
                return Response::html("@admin/register-member.html", ['links' => $this->links, 'member' => $member, 'error' => $error])->withoutCache();
            }
        }
        throw new \Router\RouteNotFoundException();
    }

    public function showStudentsPage(StudentsRepository $repository): Response
    {
        $students = $repository->getStudentsList();
        return Response::html('@admin/students.html', ['links' => $this->links, 'students' => $students])->withoutCache();
    }

    public function showStudentRegister(Request $request, ValidatorService $validator, StudentsRepository $repository): Response
    {
        $userId = Session::getFlash('adminUpdateUserId') ?? $validator->validateInt($request->__get('user-id')) ?: Session::getFlash('reloadUserId');
        $error = Session::getFlash('updateRegisterError');

        if($userId) {
            Session::flash('reloadUserId', $userId);
            $member = $repository->getProfileInfo($userId);
            if($member) {
                return Response::html("@admin/register-student.html", ['links' => $this->links, 'member' => $member, 'error' => $error])->withoutCache();
            }
        }
        throw new \Router\RouteNotFoundException();
    }
}