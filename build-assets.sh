#!/bin/bash

# Descobre o diretório absoluto do script para referência de caminhos internos
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" &> /dev/null && pwd)"

# Função para buscar executáveis subindo a árvore de diretórios
find_binary() {
    local current_dir="$1"
    local target_file="$2"

    while [[ "$current_dir" != "/" ]]; do
        if [[ -f "$current_dir/$target_file" && -x "$current_dir/$target_file" ]]; then
            echo "$current_dir/$target_file"
            return 0
        fi
        current_dir=$(dirname "$current_dir")
    done
    return 1
}

echo -e "🔍 Localizando dependências...\n"

# Identifica o esbuild (verifica o PATH global primeiro, depois busca nos diretórios)
if command -v esbuild &> /dev/null; then
    ESBUILD_BIN="esbuild"
else
    ESBUILD_BIN=$(find_binary "$SCRIPT_DIR" "esbuild")
fi

# Identifica o sass (verifica o PATH global primeiro, depois busca nos diretórios)
if command -v sass &> /dev/null; then
    SASS_BIN="sass"
else
    SASS_BIN=$(find_binary "$SCRIPT_DIR" "dart-sass/sass")
fi

# Aborta a execução caso algum binário não seja encontrado
if [[ -z "$ESBUILD_BIN" || -z "$SASS_BIN" ]]; then
    echo "❌ Erro: Binários do esbuild e/ou sass não foram localizados na árvore de diretórios."
    exit 1
fi

# Definição dos caminhos absolutos relativos ao local do script
JS_SRC="$SCRIPT_DIR/javascript"
JS_DEST="$SCRIPT_DIR/site/public_html/assets/js"

SCSS_SRC="$SCRIPT_DIR/scss"
CSS_DEST="$SCRIPT_DIR/site/public_html/assets/css"

echo -e "🧹 Iniciando limpeza...\n"

if [ -d "$JS_DEST" ]; then
    find "$JS_DEST" -mindepth 1 -maxdepth 1 ! -name "libs" -exec rm -rf {} +
else
    mkdir -p "$JS_DEST"
fi

if [ -d "$CSS_DEST" ]; then
    find "$CSS_DEST" -mindepth 1 -maxdepth 1 ! -name "libs" -exec rm -rf {} +
else
    mkdir -p "$CSS_DEST"
fi

echo "🔨 Compilando Javascript..."

"$ESBUILD_BIN" $(find "$JS_SRC" -type f -name "*.js" ! -name "_*") \
    --bundle \
    --minify \
    --outdir="$JS_DEST" \
    --outbase="$JS_SRC"

echo -e "\n🎨 Compilando SCSS...\n"

"$SASS_BIN" "$SCSS_SRC":"$CSS_DEST" \
    --style=compressed \
    --no-source-map

echo -e "✅ Build de Produção concluído com sucesso!\n"
echo "📂 JS em: $JS_DEST"
echo "📂 CSS em: $CSS_DEST"
