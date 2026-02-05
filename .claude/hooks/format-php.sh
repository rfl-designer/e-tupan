#!/bin/bash
# Formata arquivos PHP com Pint após edição

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

# Só formatar arquivos PHP
if [[ "$FILE_PATH" != *.php ]]; then
    exit 0
fi

# Verificar se o arquivo existe
if [[ ! -f "$FILE_PATH" ]]; then
    exit 0
fi

# Rodar Pint no arquivo
cd "$CLAUDE_PROJECT_DIR" || exit 0
vendor/bin/pint "$FILE_PATH" --quiet 2>/dev/null

exit 0
