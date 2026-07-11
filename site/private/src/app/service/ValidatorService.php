<?php 
namespace App\Service;

use App\Exception\ValidationException;
use App\Model\Address;
use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use App\Model\EmergencyInfo;
use App\Model\PersonalContact;
use App\Model\PersonalData;

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

    public static function validateCpf(string $cpf): string|false
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

    public static function isNumber(string $number): bool
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

    public static function validatePersonalName(string $name): string|false
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

    public static function isAlphaNum(string $string): bool
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

    public static function validatePhoneNumber(string $number): string|false
    {
        $number = filter_var(preg_replace('/[^0-9]/is', '', $number ?? ''), FILTER_SANITIZE_NUMBER_INT);
        return (strlen($number) === 11 || strlen($number) === 10)? $number:false;
    }

    public static function validateInt(string $input): false|int
    {
        $number = self::validateNumber($input);
        return filter_var($number, FILTER_VALIDATE_INT);
    }

    public static function validateEmail(string $email): string|false
    {
        $email = filter_var(strtolower(trim($email ?? '')), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validateString(string $string)
    {
        return (!empty(trim((string)$string)))? trim((string)$string):'';
    }

    public static function validateDatetime(string $date, $inputFormat = 'Y-m-d\TH:i', $outputFormat = 'Y-m-d H:i:s')
    {
        $date = self::validateString($date);
        if(empty($date)) return false;
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $datetime = DateTime::createFromFormat($inputFormat, $date, $timezone);
    
        return ($datetime->format($inputFormat) === $date)? $datetime->format($outputFormat):false;
    }

    public function validatePersonalData(array $data, bool $withAddress = true) : PersonalData
    {
        $errors = [];
        $validatedData = [];

        $fullName= $this->validatePersonalName($data['full-name']);

        if($fullName !== false) {
            $validatedData['full-name'] = $fullName;
        } else {
            $errors['full-name'] = 'Nome completo ausente ou inválido!';
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

        try {
            $validatedData['pronouns'] = $this->validatePronouns($data);
        } catch (ValidationException $exception) {
            $errors = array_merge($errors, $exception->getErrors());
        }

        $genderIdentity = $this->validateString($data['gender-identity']);

        if($genderIdentity !== '') {
            $validatedData['gender-identity'] = $genderIdentity;
        } else {
            $errors['gender-identity'] = 'Identidade de gênero ausente ou inválido!';
        }

        $ethnicity = $this->validateString($data['ethnicity']);

        if($ethnicity !== '') {
            $validatedData['ethnicity'] = $ethnicity;
        } else {
            $errors['ethnicity'] = 'Identidade de gênero ausente ou inválido!';
        }

        
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
        
        if($withAddress) {
            try {
                $validatedData['address'] = $this->validateAddress($data);
            } catch (ValidationException $exception) {
                $errors = array_merge($errors, $exception->getErrors());
            }
        } else {
            $validatedData['address'] = null;
        }

        if(!empty($errors)) {
            throw new ValidationException($errors);
        }

        return new PersonalData(
            fullName: $validatedData['full-name'],
            useSocialName: $validatedData['use-social-name'],
            socialName: $validatedData['social-name'],
            nickname: $validatedData['nickname'],
            pronouns: $validatedData['pronouns'],
            genderIdentity: $validatedData['gender-identity'],
            ethnicity: $validatedData['ethnicity'],
            cpf: $validatedData['cpf'],
            birthDate: new DateTimeImmutable($validatedData['birth-date']),
            email: $validatedData['email'],
            phone: $validatedData['phone'],
            address: $validatedData['address']
        );
    }

    public function validateEmergencyInfo(array $data): EmergencyInfo
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
            city: $validatedData['city'],
            state: $validatedData['state']
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

    public function validatePronouns(array $data): array
    {
        $errors = [];
        $validatedPronouns = [];

        if (isset($data['pronouns-not-specified'])) {
            return ['Prefiro não informar'];
        }

        if (isset($data['pronouns-he-his'])) {
            $validatedPronouns[] = 'Ele/dele';
        }

        if (isset($data['pronouns-she-her'])) {
            $validatedPronouns[] = 'Ela/dela';
        }

        if (isset($data['pronouns-they-them'])) {
            $validatedPronouns[] = 'Elu/delu';
        }

        if (isset($data['pronouns-others'])) {
            $customText = isset($data['pronouns-others-text']) ? $data['pronouns-others-text'] : '';
            $sanitizedCustomText = $this->validateString($customText);

            if ($sanitizedCustomText !== '') {
                $validatedPronouns[] = $sanitizedCustomText;
            } else {
                $errors['pronouns-others-text'] = 'O texto personalizado é obrigatório quando a opção "Outro" é selecionada.';
            }
        }

        if (empty($validatedPronouns) && empty($errors)) {
            $errors['pronouns'] = 'Nenhuma opção de pronome foi selecionada.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validatedPronouns;
    }
}
