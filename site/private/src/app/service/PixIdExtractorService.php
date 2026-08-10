<?php
namespace App\Service;

use App\Util\Log;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\AnnotateFileRequest;
use Google\Cloud\Vision\V1\InputConfig;
use Google\Cloud\Vision\V1\BatchAnnotateFilesRequest;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Exception;

class PixIdExtractorService
{
    private array $clientConfig;

    /**
     * @param string $credentialsPath Caminho absoluto para o arquivo JSON da Service Account
     */
    public function __construct(?string $credentialsPath = null)
    {
        $this->clientConfig = [
            'credentials' => $credentialsPath ?? DIR_SECRETS . 'cloud-vision-credentials.json'
        ];
    }

    /**
     * Extrai o ID End-to-End de um comprovante Pix.
     */
    public function extractE2eId(string $fileContent, string $mimeType): ?string
    {

        $client = new ImageAnnotatorClient($this->clientConfig);
        $extractedText = '';

        try {

            if ($mimeType === 'application/pdf') {
                $extractedText = $this->processPdf($client, $fileContent, $mimeType);
            } else {
                $extractedText = $this->processImage($client, $fileContent);
            }

            return $this->findPixId($extractedText);

        } catch (Exception $exception) {
            Log::error('PixIdExtractorService | Erro ao extrair id: ', 'error.log', $exception->getMessage());
            return null;
        } finally {
            $client->close();
        }
    }

    /**
     * Processamento específico para arquivos PDF (até 5 páginas de forma síncrona)
     */
    private function processPdf(ImageAnnotatorClient $client, string $content, string $mimeType): string
    {
        $inputConfig = (new InputConfig())
            ->setMimeType($mimeType)
            ->setContent($content);

        $feature = (new Feature())->setType(Type::DOCUMENT_TEXT_DETECTION);

        $request = (new AnnotateFileRequest())
            ->setInputConfig($inputConfig)
            ->setFeatures([$feature]);

        // Encapsula o array de requisições no objeto esperado
        $batchRequest = (new BatchAnnotateFilesRequest())
            ->setRequests([$request]);

        $response = $client->batchAnnotateFiles($batchRequest);
        $responses = $response->getResponses();
        $text = '';

        if (count($responses) > 0) {
            $annotateImageResponses = $responses[0]->getResponses();
            foreach ($annotateImageResponses as $res) {
                if ($annotation = $res->getFullTextAnnotation()) {
                    $text .= $annotation->getText() . ' ';
                }
            }
        }

        return $text;
    }

    /**
     * Processamento direto para imagens (PNG, JPG, etc)
     */
    private function processImage(ImageAnnotatorClient $client, string $content): string
    {
        $image = (new Image())
            ->setContent($content);

        $feature = (new Feature())
            ->setType(Type::DOCUMENT_TEXT_DETECTION);

        $request = (new AnnotateImageRequest())
            ->setImage($image)
            ->setFeatures([$feature]);

        $batchRequest = (new BatchAnnotateImagesRequest())
            ->setRequests([$request]);

        $response = $client->batchAnnotateImages($batchRequest);
        $responses = $response->getResponses();

        if (count($responses) > 0) {
            $annotation = $responses[0]->getFullTextAnnotation();
            return $annotation ? $annotation->getText() : '';
        }

        return '';
    }

    /**
     * Busca o padrão do ID Pix E2E no texto extraído
     */
    private function findPixId(string $text): ?string
    {
        // O ID E2E do Pix possui 32 caracteres: 
        // 'E' + 8 dígitos (ISPB) + 12 dígitos (data/hora YYYYMMDDHHMM) + 11 alfanuméricos randômicos.
        // A flag \b garante que a string não faça parte de uma sequência maior acidental.
        if (preg_match('/\b(E[a-zA-Z0-9]{31})\b/', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }
}