<?php

namespace App\Util;

use App\Model\File;
use App\Exception\FileException;
use finfo;
use Symfony\Component\Mime\MimeTypes;

class FileManager
{
    private function __construct() {}
    private function __clone() {}

    /**
     * Valida a integridade do upload e retorna o MIME Type real.
     */
    public static function validateUpload(array $fileArray, array $allowedMimeTypes = [], int $maxSize = 2097152): string
    {
        if (isset($fileArray['error']) && !is_array($fileArray['error'])) {

            if ($fileArray['error'] === UPLOAD_ERR_OK) {

                $errors = [];

                if($fileArray['size'] === 0) {
                   $errors['size'] = 'O arquivo enviado está vazio.';
                } else if($fileArray['size'] > $maxSize) {
                   $errors['size'] = 'O arquivo é maior que o tamanho máximo permitido.';
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $realMimeType = $finfo->file($fileArray['tmp_name']);

                if($realMimeType === false) {
                    $errors['mimetype'] = 'Não foi possível determinar o tipo do arquivo.';
                } else {

                    if (!empty($allowedMimeTypes)) {
                        $isValid = false;
                        foreach ($allowedMimeTypes as $allowed) {
                            
                            if ($allowed === $realMimeType) {
                                $isValid = true;
                                break;
                            }
                            
                            if (str_ends_with($allowed, '/*')) {
                                $baseType = substr($allowed, 0, -1);
                                if (str_starts_with($realMimeType, $baseType)) {
                                    $isValid = true;
                                    break;
                                }
                            }
                        }

                        if (!$isValid) {
                            $errors['mimetype'] = 'O tipo de arquivo enviado não é permitido.';
                        }
                    }
                }

                if(!empty($errors)) {
                    throw new FileException($errors);
                }

                return $realMimeType;

            } else {
                throw new FileException(['upload' => 'Falha no upload. Código de erro: ' . $fileArray['error']]);
            }
            
        } else {
            throw new FileException(['parameter' => 'Parametros de upload do arquivo inválidos.']);
        }
    }

    /**
     * Lê o conteúdo do arquivo recém-enviado diretamente para a memória (ex: CSV).
     */
    public static function getUploadedContent(array $fileArray, array $allowedMimeTypes = [], int $maxSize = 2097152): string
    {
        self::validateUpload($fileArray, $allowedMimeTypes, $maxSize);

        if (!is_uploaded_file($fileArray['tmp_name'])) {
            throw new FileException(['read' => 'Arquivo temporário inválido.']);
        }

        $content = file_get_contents($fileArray['tmp_name']);

        if ($content === false) {
            throw new FileException(['read' => 'Falha ao ler o conteúdo do arquivo enviado.']);
        }

        return $content;
    }

    /**
     * Valida e salva o arquivo no disco, com criptografia opcional, retornando o Model File.
     */
    public static function saveUpload(
        array $fileArray,  
        bool $encrypt = false, 
        string $encryptionAAD = '',
        array $allowedMimeTypes = [],
        int $maxSize = 2097152,
        ?string $relativePath = '',
        bool $returnContent = false
    ): File {
        
        $realMimeType = self::validateUpload($fileArray, $allowedMimeTypes, $maxSize);

        $originalName = basename($fileArray['name']);
        
        $relativePath = trim($relativePath, '/');
        $absoluteDir = rtrim(DIR_PRIVATE_DOCUMENTS, '/');
        
        if ($relativePath !== '') {
            $absoluteDir .= '/' . $relativePath;
        }

        if (!is_dir($absoluteDir)) {
            if (!mkdir($absoluteDir, 0755, true)) {
                throw new FileException(['save' => 'Falha ao criar o diretório de destino.']);
            }
        }

        if ($encrypt) {
            $extensionSuffix = '.enc';
        } else {
            $mimeTypes = new MimeTypes();
            $extensions = $mimeTypes->getExtensions($realMimeType);
            $extensionSuffix = !empty($extensions) ? '.' . $extensions[0] : '';
        }
        
        do {
            $storedName = bin2hex(random_bytes(24)) . $extensionSuffix;
            $destinationPath = $absoluteDir . '/' . $storedName;
        } while (file_exists($destinationPath));

        if ($encrypt) {
            
            if (!is_uploaded_file($fileArray['tmp_name'])) {
                throw new FileException(['read' => 'Arquivo temporário inválido.']);
            }

            $fileContent = file_get_contents($fileArray['tmp_name']);

            if ($fileContent === false) {
                throw new FileException(['read' => 'Falha ao ler o conteúdo do arquivo enviado.']);
            }

            $encryptedContent = Crypto::encrypt($fileContent, $encryptionAAD, true);

            if (file_put_contents($destinationPath, $encryptedContent) === false) {
                throw new FileException(['save' => 'Falha ao salvar o arquivo criptografado no disco.']);
            }

            unlink($fileArray['tmp_name']);

        } else {

            $fileContent = ($returnContent)? file_get_contents($fileArray['tmp_name']):null;

            if (!move_uploaded_file($fileArray['tmp_name'], $destinationPath)) {
                throw new FileException(['save' => 'Falha ao mover o arquivo enviado para o diretório de destino.']);
            }
        }

        return new File(
            originalName: $originalName,
            storedName: $storedName,
            path: $relativePath,
            mimeType: $realMimeType,
            size: (int) $fileArray['size'],
            isEncrypted: $encrypt,
            content: $returnContent? $fileContent:null
        );
    }

    /**
     * Recupera e lê o conteúdo de um arquivo salvo no disco.
     */
    public static function readFromDisk(File $file, string $encryptionAAD = ''): string
    {
        $filePath = rtrim(DIR_PRIVATE_DOCUMENTS, '/') . '/' . 
                    ($file->path !== '' ? $file->path . '/' : '') . 
                    $file->storedName;

        if (!file_exists($filePath)) {
            throw new FileException(['read' => 'Arquivo não encontrado no disco.']);
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new FileException(['read' => 'Falha ao ler o arquivo do disco.']);
        }

        if ($file->isEncrypted) {
            $decryptedContent = Crypto::decrypt($content, $encryptionAAD, true);

            if($decryptedContent === false) {
                throw new FileException(['read' => 'Falha ao descriptografar o arquivo.']);
            }

            return $decryptedContent;
        }

        return $content;
    }

    public static function moveLocalFile(
        string $sourcePath,  
        bool $encrypt = false, 
        string $encryptionAAD = '',
        ?string $relativePath = '',
        bool $returnContent = false
    ): File {
        
        if (!file_exists($sourcePath)) {
            throw new FileException(['read' => 'O arquivo de origem não foi encontrado no servidor.']);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMimeType = $finfo->file($sourcePath);
        $fileSize = (int) filesize($sourcePath);
        $originalName = basename($sourcePath);
        
        $relativePath = trim($relativePath, '/');
        $absoluteDir = rtrim(DIR_PRIVATE_DOCUMENTS, '/');
        
        if ($relativePath !== '') {
            $absoluteDir .= '/' . $relativePath;
        }

        if (!is_dir($absoluteDir)) {
            if (!mkdir($absoluteDir, 0755, true)) {
                throw new FileException(['save' => 'Falha ao criar o diretório de destino.']);
            }
        }

        if ($encrypt) {
            $extensionSuffix = '.enc';
        } else {
            $mimeTypes = new MimeTypes();
            $extensions = $mimeTypes->getExtensions($realMimeType);
            $extensionSuffix = !empty($extensions) ? '.' . $extensions[0] : '';
        }
        
        do {
            $storedName = bin2hex(random_bytes(24)) . $extensionSuffix;
            $destinationPath = $absoluteDir . '/' . $storedName;
        } while (file_exists($destinationPath));

        if ($encrypt) {
            
            $fileContent = file_get_contents($sourcePath);

            if ($fileContent === false) {
                throw new FileException(['read' => 'Falha ao ler o conteúdo do arquivo de origem.']);
            }

            $encryptedContent = Crypto::encrypt($fileContent, $encryptionAAD, true);

            if (file_put_contents($destinationPath, $encryptedContent) === false) {
                throw new FileException(['save' => 'Falha ao salvar o arquivo criptografado no disco.']);
            }

            unlink($sourcePath);

        } else {

            $fileContent = ($returnContent) ? file_get_contents($sourcePath) : null;

            if (!rename($sourcePath, $destinationPath)) {
                throw new FileException(['save' => 'Falha ao mover o arquivo para o diretório de destino.']);
            }
        }

        return new File(
            originalName: $originalName,
            storedName: $storedName,
            path: $relativePath,
            mimeType: $realMimeType,
            size: $fileSize,
            isEncrypted: $encrypt,
            content: $returnContent ? $fileContent : null
        );
    }
}