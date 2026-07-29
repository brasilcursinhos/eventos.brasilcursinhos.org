<?php 
namespace App\Validation;

use App\Enum\Status\EventRegistrationStatus;
use App\Enum\Type\EventRegistrationType;
use App\Service\ValidatorService;
use App\Exception\ValidationException;
use App\Model\EventRegistration;

class EventRegistrationValidator
{
    private ValidatorService $validator;

    public function __construct(ValidatorService $validator)
    {
        $this->validator = $validator;
    }

    public function validateEncupRegistration(array $data): EventRegistration
    {
        $errors = [];
        $validatedData = [];

        try {
            $validatedData['additional-data'] = ['dietaryRestrictions' => $this->validateDietaryRestrictions($data)];
        } catch (ValidationException $exception) {
            $errors = array_merge($errors, $exception->getErrors());
        }

        try {
            $validatedData['emergency-data'] = $this->validator->validateEmergencyInfo($data);
        } catch (ValidationException $exception) {
            $errors = array_merge($errors, $exception->getErrors());
        }

        if(isset($data['event-registration-type'])) {
            if($data['event-registration-type'] === 'normal' || $data['event-registration-type'] === 'staffcup') {
                if(isset($data['participates-cup'])) {
                    if($data['participates-cup'] === 'yes') {
                        $validatedData['type'] = (isset($data['participates-affiliated-cup']) 
                            && $data['participates-affiliated-cup'] === 'yes')? 
                            EventRegistrationType::AFFILIATED_CUP_MEMBER:EventRegistrationType::UNAFFILIATED_CUP_MEMBER;
                    } else {
                        $validatedData['type'] = EventRegistrationType::EXTERNAL_PARTICIPANT;
                    }
                } else {
                    $errors['participates-cup'] = 'Participação em cursinho popular ausente.';
                }
            } else if($data['event-registration-type'] === 'staffbc') {
                $validatedData['type'] = EventRegistrationType::STAFF;
            } else if($data['event-registration-type'] === 'palestrante') {
                $validatedData['type'] = EventRegistrationType::SPEAKER;
            } else if($data['event-registration-type'] === 'convidado') {
                $validatedData['type'] = EventRegistrationType::GUEST;
            } else {
                $errors['event-registration-type'] = 'Tipo de inscrição é inválido.';
            }
        } else {
            $errors['event-registration-type'] = 'Tipo de inscrição ausente.';
        }

        $organizationName = $this->validator->validatePersonalName($data['organization-name']);
        
        if($organizationName !== false) {
            $validatedData['organization-name'] = $organizationName;
        } else {
            $errors['organization-name'] = 'Nome da organização ausente ou inválido!';
        }

        $ticketId = $this->validator->validateInt($data['ticket-id']);

        if($ticketId !== false) {
            $validatedData['ticket-id'] = $ticketId;
        } else {
            $errors['ticket-id'] = 'ID do ingresso ausente ou inválido!';
        }

        $eventId = $this->validator->validateInt($data['event-id']);

        if($eventId !== false) {
            $validatedData['event-id'] = $eventId;
        } else {
            $errors['event-id'] = 'ID do evento ausente ou inválido!';
        }

        $userId = $this->validator->validateInt($data['user-id']);

        if($userId !== false) {
            $validatedData['user-id'] = $userId;
        } else {
            $errors['user-id'] = 'ID do usuário ausente ou inválido!';
        }

        return new EventRegistration(
            id: null,
            registration: null,
            type: $validatedData['type'],
            status: EventRegistrationStatus::PENDING_PAYMENT,
            organizationName: $validatedData['organization-name'],
            additionalData: $validatedData['additional-data'],
            emergencyData: $validatedData['emergency-data'],
            ticketId: $validatedData['ticket-id'],
            eventId: $validatedData['event-id'],
            userId: $validatedData['user-id']
        );
    }

    private function validateDietaryRestrictions(array $data): array
    {
        $errors = [];
        $validatedDietaryRestrictions = [];

        if (isset($data['no-restriction'])) {
            return ['Nenhuma restrição alimentar'];
        }

        if (isset($data['vegan'])) {
            $validatedDietaryRestrictions[] = 'Vegano';
        }

        if (isset($data['vegetarian'])) {
            $validatedDietaryRestrictions[] = 'Vegetariano';
        }

        if (isset($data['lactose-intolerance'])) {
            $validatedDietaryRestrictions[] = 'Intolerância à lactose';
        }

        if (isset($data['gluten-intolerance'])) {
            $validatedDietaryRestrictions[] = 'Doença Celíaca / Intolerância ao glúten';
        }

        if (isset($data['sugar-restriction'])) {
            $validatedDietaryRestrictions[] = 'Diabetes / Restrição de ingestão de açúcar';
        }

        if (isset($data['nut-allergy'])) {
            $validatedDietaryRestrictions[] = 'Alergia a amendoim, nozes ou castanhas';
        }
        
        if (isset($data['egg-allergy'])) {
            $validatedDietaryRestrictions[] = 'Alergia a ovos';
        }

        if (isset($data['seafood-allergy'])) {
            $validatedDietaryRestrictions[] = 'lergia a frutos do mar';
        }

        if (isset($data['cow-milk-allergy'])) {
            $validatedDietaryRestrictions[] = 'Alergia à proteína do leite de vaca (APLV)';
        }

        if (isset($data['other-restriction'])) {
            $customText = isset($data['other-restriction-text']) ? $data['other-restriction-text'] : '';
            $sanitizedCustomText = $this->validator->validateString($customText);

            if ($sanitizedCustomText !== '') {
                $validatedDietaryRestrictions[] = $sanitizedCustomText;
            } else {
                $errors['other-restriction-text'] = 'O texto personalizado é obrigatório quando a opção "Outro" é selecionada.';
            }
        }

        if (empty($validatedDietaryRestrictions) && empty($errors)) {
            $errors['restrictions'] = 'Nenhuma opção de restrição alimentar foi selecionada.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $validatedDietaryRestrictions;
    }
}