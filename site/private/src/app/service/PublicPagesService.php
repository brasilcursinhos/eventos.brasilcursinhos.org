<?php 
namespace App\Service;

use App\Enum\SystemStatus;
use App\Exception\EmailException;
use App\Util\Email;
use App\Validation\ContactEmailValidator;
use App\Exception\ValidationException;
use App\Model\Result;
use App\Util\Log;
use Router\Request;

class PublicPagesService
{
    private ContactEmailValidator $validator;

    public function __construct(ContactEmailValidator $validator)
    {
        $this->validator = $validator;
    }

    public function sendContactEmail(Request $request): Result
    {
        $data = $request->all();

        try {
            
            $validatedData = $this->validator->validate($data);
            
            $now = new \DateTime('now');
            
            $email = Email::create();
            
            $email->subject('[CONTATO] ' . $validatedData->subject);
            $email->replyTo($validatedData->sender, $validatedData->name);
            $email->to($validatedData->recipient);
            $email->renderBody('contact-email.html', ['data' => $validatedData, 'sendDate' => $now->format('d/m/Y à\s H:i:s')]);

            if(!$email->send()) {
                throw new EmailException();
            }

            return Result::success();

        } catch(ValidationException $exception) {

            return Result::failure(
                SystemStatus::VALIDATION_ERROR,
                $exception->getMessage(), 
                $exception->getErrors()
            );

        } catch(EmailException $exception) {

            return Result::failure(
                SystemStatus::EMAIL_ERROR,
                $exception->getMessage()
            );

        } catch(\Exception $exception) {

            return Result::failure(
                SystemStatus::UNKNOWN_ERROR,
                $exception->getMessage()
            );

        }
    }

    public function cspReport(Request $request): void
    {
        $data = $request->all();

        if (empty($data)) {
            Log::info('csp-report vazio ou formato inválido.', 'csp.log');
            return;
        }

        try {

            $reports = [];

            if (isset($data["csp-report"])) {
                $reports[] = $data["csp-report"];
            } elseif (isset($data[0])) {
                foreach($data as $violation) {
                    $body = $violation['body'] ?? [];
                    $reports[] = [
                        'blocked-uri'        => $body['blockedURL']         ?? null,
                        'document-uri'       => $body['documentURL']        ?? null,
                        'violated-directive' => $body['effectiveDirective'] ?? null,
                        'source-file'        => $body['sourceFile']         ?? null
                    ];
                } 
            }

            foreach($reports as $report) {

                $blocked   = $report['blocked-uri'] ?? 'unknown';
                $directive = $report['violated-directive'] ?? 'unknown';
                $page      = $report['document-uri'] ?? 'unknown';
                $source    = $report['source-file'] ?? 'N/A';
                
                $logMessage = sprintf(
                    "CSP Violation: Bloqueou '%s' (Diretiva: %s) em %s. Origem: %s",
                    $blocked, $directive, $page, $source
                );

                Log::info($logMessage, 'csp.log');
            }
        } catch (\Exception $exception) {
            Log::error('Falha ao processar CSP report', 'csp.log', $exception->getMessage());
        }
    }
}