<?php 
namespace App\Util;

use PHPMailer\PHPMailer\PHPMailer;
use App\Util\EmailViewer;
use App\Util\Log;
use App\Util\Cache;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class Email
{
    private Google_Client $client;
    private string $from = '';
    private string $name ='';
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private array $replyTo = [];
    private string $subject = '';
    private string $htmlBody = '';
    private string $textBody = '';
    private array $attachments = [];
    private const CACHE_KEY = 'EMAIL_ACCESS_TOKEN';

    private function __construct()
    {
        $this->client = new Google_Client();
        $this->from = $this->getFrom();
        $this->name = $this->getName();
        $this->client->setApplicationName('Sistema - Eventos Brasil Cursinhos');
        $this->client->setClientId($this->getClientId());
        $this->client->setClientSecret($this->getClientSecret());
        $this->client->setAccessType('offline');
        $this->client->setAccessToken($this->getAccessToken());
    }

    private function __clone() {}

    private function getFrom(): string
    {
        return $_ENV['EMAIL_ADDRESS'] ?? '';
    }

    private function getName(): string
    {
        return $_ENV['EMAIL_NAME'] ?? '';
    }

    private function getClientId(): string
    {
        return $_ENV['EMAIL_CLIENT_ID'] ?? '';
    }

    private function getClientSecret(): string
    {
        return $_ENV['EMAIL_CLIENT_SECRET'] ?? '';
    }

    private function getRefreshToken(): string
    {
        return $_ENV['EMAIL_REFRESH_TOKEN'] ?? '';
    }

    private function getAccessToken(): string
    {
        $cachedToken = Cache::get(self::CACHE_KEY);
        if($cachedToken) {
            return $cachedToken;
        }
        try {
            $this->client->fetchAccessTokenWithRefreshToken($this->getRefreshToken());
            $accessToken = $this->client->getAccessToken();
            $expiresIn = $accessToken['expires_in'] ?? 3599;
            Cache::set(self::CACHE_KEY, $accessToken['access_token'], $expiresIn - 300);
            return $accessToken['access_token'];
        } catch(\Exception $exception) {
            Log::error('Erro ao obter AccessToken', 'email.log', $exception->getMessage());
            return '';
        }
    }

    private function buildMimeMessage(): string|false
    {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($this->from, $this->name);
            foreach($this->to as $address) {
                $mail->addAddress($address[0], $address[1]);
            }
            foreach($this->cc as $address) {
                $mail->addCC($address[0], $address[1]);
            }
            foreach($this->bcc as $address) {
                $mail->addBCC($address[0], $address[1]);
            }
            foreach($this->replyTo as $address) {
                $mail->addReplyTo($address[0], $address[1]);
            }
            foreach($this->attachments as $attachment) {
                if($attachment[2]) {
                    $mail->addAttachment($attachment[0], $attachment[1]);
                } else {
                    $mail->addStringAttachment($attachment[0], $attachment[1]);
                }
            }
            $mail->Subject = $this->subject;
            $mail->Body = $this->htmlBody;
            $mail->AltBody = $this->textBody;
            if (!empty($this->htmlBody)) {
                $mail->isHTML(true);
            }

            if (!$mail->preSend()) {
                return false;
            }

            return $mail->getSentMIMEMessage();
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function executeSend(): bool
    {
        try {
            $mimeMessage = $this->buildMimeMessage();
            if($mimeMessage === false) {
                return false;
            }
            $encodedMessage = rtrim(strtr(base64_encode($mimeMessage), '+/', '-_'), '=');
            $message = new Google_Service_Gmail_Message();
            $message->setRaw($encodedMessage);
            $service = new Google_Service_Gmail($this->client);
            $service->users_messages->send('me', $message);
            return true;
        } catch(\Exception $exception) {
            return false;
        }
    }

    public static function create(): self
    {
        return new self();
    }

    public function send(): bool
    {
        return $this->executeSend();
    }

    public function to(string $email, string $name = ''): self
    {
        $this->to[] = [$email, $name];
        return $this;
    }

    public function cc(string $email, string $name = ''): self
    {
        $this->cc[] = [$email, $name];
        return $this;
    }

    public function bcc(string $email, string $name = ''): self
    {
        $this->bcc[] = [$email, $name];
        return $this;
    }
    
    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo[] = [$email, $name];
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function htmlBody(string $html): self
    {
        $this->htmlBody = $html;
        return $this;
    }

    public function renderHtmlBody(string $template, array $parameters = []): self
    {
        $this->htmlBody = EmailViewer::getHtml($template, $parameters);
        return $this;
    }

    public function renderTextBody(string $template, array $parameters = []): self
    {
        $this->textBody = EmailViewer::getText($template, $parameters);
        return $this;
    }

    public function renderBody(string $template, array $parameters = []): self
    {
        $this->renderHtmlBody($template, $parameters);
        $this->renderTextBody($template, $parameters);
        return $this;
    }

    public function attachFile(string $path, string $name = ''): self
    {
        $this->attachments[] = [$path, $name, true];
        return $this;
    }

    public function attachString(string $content, string $name): self
    {
        $this->attachments[] = [$content, $name, false];
        return $this;
    }
}