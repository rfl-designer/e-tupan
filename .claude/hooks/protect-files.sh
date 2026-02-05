#!/bin/bash
# Bloqueia edição de arquivos sensíveis

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

# Arquivos e padrões protegidos
PROTECTED_PATTERNS=(
    ".env"
    "auth.json"
    "composer.lock"
    "package-lock.json"
    ".git/"
    "storage/oauth"
    "bootstrap/cache"
)

for pattern in "${PROTECTED_PATTERNS[@]}"; do
    if [[ "$FILE_PATH" == *"$pattern"* ]]; then
        echo "Bloqueado: '$FILE_PATH' corresponde ao padrão protegido '$pattern'" >&2
        exit 2
    fi
done

exit 0
