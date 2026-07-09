<?php 
namespace App\Service;

use App\Enum\StudentCandidateType;
use App\Exception\ValidationException;
use App\Model\Address;
use App\Model\BankAccount;
use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use App\Model\EmergencyInfo;
use App\Model\IdCard;
use App\Model\PersonalContact;
use App\Model\PersonalData;
use App\Model\SchoolInfo;

class ValidatorService
{

    public static function validatePassword(string $password): string|false
    {
        if (mb_strlen($password) < 8) {
            return false;
        }

        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasNumber    = preg_match('/\d/', $password);

        if ($hasLowercase && $hasUppercase && $hasNumber) {
            return $password;
        }

        return false;
    }

    public static function validateCpf($cpf): string|false
    {
        $cpf = filter_var(preg_replace( '/[^0-9]/is', '', $cpf ?? ''), FILTER_SANITIZE_NUMBER_INT);
        if(strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf ?? '')) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return $cpf;
    }

    public static function isNumber($number): bool
    {
        for($i = 0; $i < strlen($number); $i++) {
            if(!(is_numeric($number[$i]) || $number[$i] === '-' || $number[$i] === '.')) {
                return false;
            }
        }
        if(!empty($number)) {
            return true;
        }
        return false;
    }

    public static function validatePersonalName($name): string|false
    {
        $exceptions = ['a', 'e', 'em', 'de', 'da', 'das', 'do', 'dos'];

        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);

        if ($name === '') {
            return false;
        }

        $words = explode(' ', $name);

        foreach ($words as $i => $word) {

            if (mb_strpos($word, '-') !== false) {
                $parts = explode('-', $word);
                foreach ($parts as $pi => $part) {
                    $partLower = mb_strtolower($part);
                    if (in_array($partLower, $exceptions, true)) {
                        $parts[$pi] = $partLower;
                    } else {
                        $parts[$pi] = mb_convert_case($partLower, MB_CASE_TITLE);
                    }
                }
                $words[$i] = implode('-', $parts);
                continue;
            }

            if (mb_strpos($word, "'") !== false || mb_strpos($word, "’") !== false) {
                $segments = preg_split("/(['’])/", $word, -1, PREG_SPLIT_DELIM_CAPTURE);
                if (count($segments) >= 3) {
                    $left = mb_strtolower($segments[0]);
                    $delim = $segments[1];
                    $right = '';
                    for ($k = 2; $k < count($segments); $k++) {
                        $right .= $segments[$k];
                    }
                    $rightLower = mb_strtolower($right);
                    $rightCap = mb_convert_case($rightLower, MB_CASE_TITLE);
                    $words[$i] = $left . $delim . $rightCap;
                } else {
                    $words[$i] = mb_convert_case($word, MB_CASE_TITLE);
                }
                continue;
            }

            $wordLower = mb_strtolower($word);
            if (in_array($wordLower, $exceptions, true)) {
                $words[$i] = $wordLower;
            } else {
                $words[$i] = mb_convert_case($wordLower, MB_CASE_TITLE);
            }
        }

        return implode(' ', $words);
    }

    public static function isAlphaNum($string): bool
    {
        for($i = 0; $i < strlen($string); $i++) {
            if(!(ctype_alnum($string[$i]) || $string[$i] === '_' || $string[$i] === '.')) {
                return false;
            }
        }
        if(!empty($string)) {
            return true;
        }
        return false;
    }

    public static function validateNumber(?string $number, ?int $length = null): string|false
    {
        $number = filter_var(preg_replace('/[^0-9]/is', '', $number ?? ''), FILTER_SANITIZE_NUMBER_INT);
        if(is_null($length)) {
            return ($number === '')? false:$number;
        } else {
            return (strlen($number) === $length)? $number:false;
        }
    }

    public static function validateRegistrationNumber(?string $input): string|false
    {
        $number = filter_var(preg_replace('/[^0-9]/is', '', $input ?? ''), FILTER_SANITIZE_NUMBER_INT);
        return (strlen($number) === 8)? $number:false;
    }

    public static function validatePhoneNumber($number): string|false
    {
        $number = filter_var(preg_replace('/[^0-9]/is', '', $number ?? ''), FILTER_SANITIZE_NUMBER_INT);
        return (strlen($number) === 11 || strlen($number) === 10)? $number:false;
    }

    public static function validateInt($input): false|int
    {
        $number = self::validateNumber($input);
        return filter_var($number, FILTER_VALIDATE_INT);
    }

    public static function validateEmail($email): string|false
    {
        $email = filter_var(strtolower(trim($email ?? '')), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateString($string)
    {
        return (!empty(trim((string)$string)))? trim((string)$string):'';
    }

    public static function validateDatetime($date, $inputFormat = 'Y-m-d\TH:i', $outputFormat = 'Y-m-d H:i:s')
    {
        $date = self::validateString($date);
        if(empty($date)) return false;
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $datetime = DateTime::createFromFormat($inputFormat, $date, $timezone);
    
        return ($datetime->format($inputFormat) === $date)? $datetime->format($outputFormat):false;
    }

    public function validatePersonalData(array $data, bool $withPersonalContact = true, bool $withIdCard = false, bool $withEmergencyInfo = true, bool $withAddress = false, bool $withBankAccount = false) : PersonalData
    {
        $errors = [];
        $validatedData = [];

        $firstName= $this->validatePersonalName($data['first-name']);

        if($firstName !== false) {
            $validatedData['first-name'] = $firstName;
        } else {
            $errors['first-name'] = 'Nome ausente ou inválido!';
        }

        $lastName= $this->validatePersonalName($data['last-name']);

        if($lastName !== false) {
            $validatedData['last-name'] = $lastName;
        } else {
            $errors['last-name'] = 'Sobrenome ausente ou inválido!';
        }
         
        $validatedData['use-social-name'] = ($data['use-social-name'] === 'yes')? true:false;

        if($validatedData['use-social-name']) {

            $socialName = $this->validatePersonalName($data['social-name']);

            if($socialName !== false) {
                $validatedData['social-name'] = $socialName;
            } else {
                $errors['social-name'] = 'Nome social ausente ou inválido!';
            }
        } else {
            $validatedData['social-name'] = null;
        }

        $cpf = $this->validateCpf($data['cpf']);

        if($cpf !== false) {
            $validatedData['cpf'] = $cpf;
        } else {
            $errors['cpf'] = 'CPF ausente ou inválido!';
        }

        $birthDate = $this->validateDatetime($data['birth-date'], 'Y-m-d');

        if($birthDate !== false) {
            $validatedData['birth-date'] = $birthDate;
        } else {
            $errors['birth-date'] = 'Data de nascimento ausente ou inválida!';
        }

        $nickname= $this->validatePersonalName($data['nickname']);

        if($nickname !== false) {
            $validatedData['nickname'] = $nickname;
        } else {
            $errors['nickname'] = 'Sobrenome ausente ou inválido!';
        }

        $pronouns = $this->validateString($data['pronouns']);

        if($pronouns !== '') {
            $validatedData['pronouns'] = $pronouns;
        } else {
            $errors['pronouns'] = 'Pronomes ausentes ou inválidos!';
        }

        $gender = $this->validateString($data['gender']);

        if($gender !== '') {
            $validatedData['gender'] = $gender;
        } else {
            $errors['gender'] = 'Sexo biológico ausente ou inválido!';
        }

        if($withPersonalContact) {
            $email = $this->validateEmail($data['email']);

            if($email !== false) {
                $validatedData['email'] = $email;
            } else {
                $errors['email'] = 'E-mail ausente ou inválido!';
            }

            $phone = $this->validatePhoneNumber($data['phone']);

            if($phone !== false) {
                $validatedData['phone'] = $phone;
            } else {
                $errors['phone'] = 'Telefone ausente ou inválido!';
            }
        } else {
            $validatedData['email'] = '-';
            $validatedData['phone'] = '-';
        }

        if($withIdCard) {
            try {
                $validatedData['idCard'] = $this->validateIdCard($data);
            } catch (ValidationException $exception) {
                $errors = array_merge($errors, $exception->getErrors());
            }
        } else {
            $validatedData['idCard'] = null;
        }

        if($withEmergencyInfo) {
            try {
                $validatedData['emergencyInfo'] = $this->validateEmergencyInfo($data);
            } catch (ValidationException $exception) {
                $errors = array_merge($errors, $exception->getErrors());
            }
        } else {
            $validatedData['emergencyInfo'] = null;
        }

        if($withAddress) {
            try {
                $validatedData['address'] = $this->validateAddress($data);
            } catch (ValidationException $exception) {
                $errors = array_merge($errors, $exception->getErrors());
            }
        } else {
            $validatedData['address'] = null;
        }

        if($withBankAccount) {
            try {
                $validatedData['bankAccount'] = $this->validateBankAccount($data);
            } catch (ValidationException $exception) {
                $errors = array_merge($errors, $exception->getErrors());
            }
        } else {
            $validatedData['bankAccount'] = null;
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new PersonalData(
            firstName: $validatedData['first-name'],
            lastName: $validatedData['last-name'],
            socialName: $validatedData['social-name'],
            nickname: $validatedData['nickname'],
            cpf: $validatedData['cpf'],
            birthDate: new DateTimeImmutable($validatedData['birth-date']),
            pronouns: $validatedData['pronouns'],
            gender: $validatedData['gender'],
            email: $validatedData['email'],
            phone: $validatedData['phone'],
            idCard: $validatedData['idCard'],
            emergencyInfo: $validatedData['emergencyInfo'],
            address: $validatedData['address'],
            bankAccount: $validatedData['bankAccount']
        );
    }

    public function validateEmergencyInfo($data): EmergencyInfo
    {
        $errors = [];
        $validatedData = [];

        $name = $this->validatePersonalName($data['emergency-contact-name']);

        if($name !== false) {
            $validatedData['name'] = $name;
        } else {
            $errors['emergency-contact-name'] = 'Nome do contato de emergência ausente ou inválido!';
        }

        $kinship = $this->validateString($data['emergency-contact-kinship']);

        if($kinship !== '') {
            $validatedData['kinship'] = $kinship;
        } else {
            $errors['emergency-contact-name'] = 'Parentesco do contato de emergência ausente ou inválido!';
        }

        $phone = $this->validatePhoneNumber($data['emergency-contact-phone']);

        if($phone !== false) {
            $validatedData['phone'] = $phone;
        } else {
            $errors['emergency-contact-phone'] = 'Telefone do contato de emergência ausente ou inválido!';
        }

        $validatedData['health-conditions'] = $this->validateString($data['health-conditions']);

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new EmergencyInfo(
            name: $validatedData['name'],
            kinship: $validatedData['kinship'],
            phone: $validatedData['phone'],
            healthConditions: $validatedData['health-conditions']
        );
    }

    public function validateSchoolInfo($data, StudentCandidateType $type) : SchoolInfo
    {
        $errors = [];
        $validatedData = [];

        if($type === StudentCandidateType::HIGH_SCHOOL_STUDENT) {

            $grade = $this->validateString($data['school-grade']);

            if($grade !== '') {
                $validatedData['grade'] = $grade;
            } else {
                 $errors['school-grade'] = 'Série ausente ou inválida!';
            }

            $class = $this->validateString($data['school-class']);

            if($class !== '') {
                $validatedData['class'] = $class;
            } else {
                 $errors['school-class'] = 'Turma ausente ou inválida!';
            }

            $shift = $this->validateString($data['school-shift']);

            if($shift !== '') {
                $validatedData['shift'] = $shift;
            } else {
                 $errors['school-shift'] = 'Turno ausente ou inválido!';
            }

        } else {

            $conclusionYear = $this->validateString($data['school-conclusion-year']);

            if($conclusionYear !== '') {
                $validatedData['conclusion-year'] = $conclusionYear;
            } else {
                 $errors['school-conclusion-year'] = 'Ano de conclusão ausente ou inválido!';
            }
        }

        $name = $this->validateString($data['school-name']);

        if($name !== '') {
            $validatedData['name'] = $name;
        } else {
            $errors['school-name'] = 'Nome da escola ausente ou inválido!';
        }

        $city = $this->validateString($data['school-city']);

        if($city !== '') {
            $validatedData['city'] = $city;
        } else {
            $errors['school-city'] = 'Cidade da escola ausente ou inválido!';
        }

        $uf = $this->validateString($data['school-uf']);

        if($uf !== '') {
            $validatedData['uf'] = $uf;
        } else {
            $errors['school-uf'] = 'UF da escola ausente ou inválido!';
        }

        $type = $this->validateString($data['school-type']);

        if($type !== '') {
            $validatedData['type'] = $type;
        } else {
            $errors['school-type'] = 'Nome da escola ausente ou inválido!';
        }

        if(isset($data['has-taken-enem'])) {
            $validatedData['has-taken-enem'] = ($data['has-taken-enem'] === 'yes')? true:false;
        } else {
            $errors['has-taken-enem'] = 'Participações em edições anteriores do ENEM não informada.';
        }

        if(isset($data['has-taken-ufsc'])) {
            $validatedData['has-taken-ufsc'] = ($data['has-taken-ufsc'] === 'yes')? true:false;
        } else {
            $errors['has-taken-ufsc'] = 'Participações em edições anteriores do Vestibular UFSC não informada.';
        }

        if(isset($data['has-taken-others-exams'])) {
            $validatedData['has-taken-others-exams'] = ($data['has-taken-others-exams'] === 'yes')? true:false;
        } else {
            $errors['has-taken-others-exams'] = 'Participações em edições anteriores de outros vestibulares não informada.';
        }

        $course = $this->validateString($data['university-course']);

        if($course !== '') {
            $validatedData['university-course'] = $course;
        } else {
            $errors['university-course'] = 'Curso universitário pretendido não informado ou ausente!';
        }

        $universityType = $this->validateString($data['university-type']);

        if($universityType !== '') {
            $validatedData['university-type'] = $universityType;
        } else {
            $errors['university-type'] = 'Tipo da universidade pretendida não informada ou ausente!';
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new SchoolInfo(
            name: $validatedData['name'],
            city: $validatedData['city'],
            uf: $validatedData['uf'],
            type: $validatedData['type'],
            hasTakenEnemExam: $validatedData['has-taken-enem'],
            hasTakenUfscExam: $validatedData['has-taken-ufsc'],
            hasTakenOthersExams: $validatedData['has-taken-others-exams'],
            intendedUniversityCourse: $validatedData['university-course'],
            intendedUniversityType: $validatedData['university-type'],
            conclusionYear: $validatedData['conclusion-year'] ?? null,
            grade: $validatedData['grade'] ?? null,
            class: $validatedData['class'] ?? null,
            shift: $validatedData['shift'] ?? null
        );
    }

    public function validateAddress(array $data): Address
    {
        $errors = [];
        $validatedData = [];

        $cep = $this->validateNumber($data['cep'], 8);

        if($cep !== false) {
            $validatedData['cep'] = $cep;
        } else {
            $errors['cep'] = 'CEP ausente ou inválido!';
        }

        $street = $this->validateString($data['street']);

        if($street !== '') {
            $validatedData['street'] = $street;
        } else {
            $errors['street'] = 'Rua ausente ou inválida!';
        }

        $number = $this->validateInt($data['number']);

        if($number !== false) {
            $validatedData['number'] = $number;
        } else {
            $errors['number'] = 'Número ausente ou inválido!';
        }

        $validatedData['complement'] = $this->validateString($data['complement']);

        $neighborhood = $this->validateString($data['neighborhood']);

        if($neighborhood !== '') {
            $validatedData['neighborhood'] = $neighborhood;
        } else {
            $errors['neighborhood'] = 'Bairro ausente ou inválido!';
        }

        $city = $this->validateString($data['city']);

        if($city!== '') {
            $validatedData['city'] = $city;
        } else {
            $errors['city'] = 'Cidade ausente ou inválida!';
        }

        $state = $this->validateString($data['state']);

        if($state !== '') {
            $validatedData['state'] = $state;
        } else {
            $errors['state'] = 'Estado ausente ou inválido!';
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new Address(
            cep: $validatedData['cep'],
            street: $validatedData['street'],
            number: $validatedData['number'],
            complement:$validatedData['complement'],
            neighborhood: $validatedData['neighborhood'],
            city: $validatedData['city'],
            state: $validatedData['state']
        );
    }

    public function validateBankAccount(array $data): BankAccount
    {
        $errors = [];
        $validatedData = [];

        $bankCode = $this->validateNumber($data['bank-code'], 3);

        if($bankCode !== false) {
            $validatedData['bankCode'] = $bankCode;
        } else {
            $errors['bank-code'] = 'Banco ausente ou inválido!';
        }

        $bankName = $this->validateString($data['bank-name']);

        if($bankName !== '') {
            $validatedData['bankName'] = $bankName;
        } else {
            $errors['bank-name'] = 'Nome do banco ausente ou inválida!';
        }

        $accountType = $this->validateString($data['account-type']);

        if($accountType !== '') {
            $validatedData['accountType'] = $accountType;
        } else {
            $errors['account-type'] = 'Tipo da conta ausente ou inválido!';
        }

        $branch = $this->validateNumber($data['branch'], 4);

        if($branch !== false) {
            $validatedData['branch'] = $branch;
        } else {
            $errors['branch'] = 'Número da agência ausente ou inválido!';
        }

        $branchDigit = $this->validateNumber($data['branch-digit'], 1);

        if($branchDigit !== false) {
            $validatedData['branchDigit'] = $branchDigit;
        } else {
            $errors['branch-digit'] = 'Dígito da agência ausente ou inválido!';
        }

        $account = $this->validateNumber($data['account']);

        if($account !== false) {
            $validatedData['account'] = $account;
        } else {
            $errors['account'] = 'Número da conta ausente ou inválido!';
        }

        $accountDigit = $this->validateNumber($data['account-digit'], 1);

        if($accountDigit !== false) {
            $validatedData['accountDigit'] = $accountDigit;
        } else {
            $errors['account-digit'] = 'Dígito da conta ausente ou inválido!';
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new BankAccount(
            bankCode: $validatedData['bankCode'],
            bankName: $validatedData['bankName'],
            accountType: $validatedData['accountType'],
            branch: $validatedData['branch'],
            branchDigit: $validatedData['branchDigit'],
            account: $validatedData['account'],
            accountDigit: $validatedData['accountDigit']
        );
    }

    public function validatePersonalContact(array $data): PersonalContact
    {
        $errors = [];
        $validatedData = [];

        $email = $this->validateEmail($data['email']);

        if($email !== false) {
            $validatedData['email'] = $email;
        } else {
            $errors['email'] = 'Email pessoal ausente ou inválido!';
        }

        $phone = $this->validatePhoneNumber($data['phone']);

        if($phone !== false) {
            $validatedData['phone'] = $phone;
        } else {
            $errors['phone'] = 'Telefone pessoal ausente ou inválido!';
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new PersonalContact(
            email: $validatedData['email'],
            phone: $validatedData['phone']
        );
    }

    public function validateIdCard(array $data): IdCard
    {
        $errors = [];
        $validatedData = [];

        $number = $this->validateNumber($data['id-number']);

        if($number !== false) {
            $validatedData['number'] = $number;
        } else {
            $errors['id-number'] = 'Número do RG ausente ou inválido!';
        }

        $issueDate = $this->validateDatetime($data['issue-date'], 'Y-m-d', 'Y-m-d');

        if($issueDate !== '') {
            $validatedData['issueDate'] = $issueDate;
        } else {
            $errors['issue-date'] = 'Data de expedição do RG ausente ou inválida!';
        }

        $issuingAuthority = $this->validateString($data['issuing-authority']);

        if($issuingAuthority !== '') {
            $validatedData['issuingAuthority'] = $issuingAuthority;
        } else {
            $errors['issuing-authority'] = 'Órgão expedidor do RG ausente ou inválido!';
        }

        $issuingState = $this->validateString($data['issuing-state']);

        if($issuingState !== '') {
            $validatedData['issuingState'] = $issuingState;
        } else {
            $errors['issuing-state'] = 'Estado de expedição do RG ausente ou inválido!';
        }

        $motherName = $this->validatePersonalName($data['mother-name']);

        if($motherName !== false) {
            $validatedData['motherName'] = $motherName;
        } else {
            $errors['mother-name'] = 'Nome da mãe ausente ou inválido!';
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new IdCard(
            number: $validatedData['number'],
            issueDate: $validatedData['issueDate'],
            issuingAuthority: $validatedData['issuingAuthority'],
            issuingState: $validatedData['issuingState'],
            motherName: $validatedData['motherName']
        );
    }
}
