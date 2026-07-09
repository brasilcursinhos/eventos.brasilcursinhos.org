# Site de Eventos Brasil Cursinhos

### Compilação dos arquivos em Assets

Na pasta principal há o arquivo build-assets.sh que gera os arquivos de css e js do site.

Talvez seja necessário ajustar a permição de execução do arquivo com:

```bash
chmod +x build-assets.sh
```

Para executar o arquivo é necessário que o sass e o esbuild estejam disponíveis globalmente no path ou que as instalações standalone estejam disponíveis na raiz do servidor. Para realizar as instações standalone utilize os comandos abaixo no diretório raiz do servidor, o script adcionará temporariamente os caminhos da instalaçao ao path para execução dos comandos.

```bash
curl -fsSL https://esbuild.github.io/dl/latest | sh

wget https://github.com/sass/dart-sass/releases/download/1.100.0/dart-sass-1.100.0-linux-x64.tar.gz
tar -xzf dart-sass-1.100.0-linux-x64.tar.gz
```

### Configuração das permissões das pastas

No ambiente de desenvolvimento/produção as pastas de logs, documentos e cache do Twig devem estar configuradas com a permissão 775

```bash
#Define o caminho para a pasta raiz do projeto
BASE_DIR="path/to/root/file"

# Altera o grupo das pastas para www-data (Apache/PHP-FPM)
sudo chown -R $USER:www-data "$BASE_DIR/site/private/logs"
sudo chown -R $USER:www-data "$BASE_DIR/site/private/src/view/emails/cache"
sudo chown -R $USER:www-data "$BASE_DIR/site/private/src/view/templates/cache"
sudo chown -R $USER:www-data "$BASE_DIR/site/private/storage/documents"
sudo chown -R $USER:www-data "$BASE_DIR/site/private/storage/cache"
sudo chown -R $USER:www-data "$BASE_DIR/site/private/storage/sessions"
sudo chown -R $USER:www-data "$BASE_DIR/site/public_html/documentos"

# Concede permissão de escrita para o grupo (775)
sudo chmod -R 775 "$BASE_DIR/site/private/logs"
sudo chmod -R 775 "$BASE_DIR/site/private/src/view/emails/cache"
sudo chmod -R 775 "$BASE_DIR/site/private/src/view/templates/cache"
sudo chmod -R 775 "$BASE_DIR/site/private/storage/documents"
sudo chmod -R 770 "$BASE_DIR/site/private/storage/cache"
sudo chmod -R 770 "$BASE_DIR/site/private/storage/sessions"
sudo chmod -R 775 "$BASE_DIR/site/public_html/documentos"

# Ativa o bit SGID para forçar a herança do grupo em novos arquivos (logs diários e arquivos de cache)
sudo chmod g+s "$BASE_DIR/site/private/logs"
sudo chmod g+s "$BASE_DIR/site/private/src/view/emails/cache"
sudo chmod g+s "$BASE_DIR/site/private/src/view/templates/cache"
sudo chmod g+s "$BASE_DIR/site/private/storage/documents"
sudo chmod g+s "$BASE_DIR/site/private/storage/cache"
sudo chmod g+s "$BASE_DIR/site/private/storage/sessions"
sudo chmod g+s "$BASE_DIR/site/public_html/documentos"

# Pasta de senhas do sistema
sudo chown -R $USER:www-data "$BASE_DIR/site/private/config/secrets"
sudo chmod 750 "$BASE_DIR/site/private/secrets"
sudo find "$BASE_DIR/site/private/secrets" -type f -exec chmod 640 {} \;
```