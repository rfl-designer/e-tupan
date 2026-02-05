#!/bin/bash
# Bloqueia comandos Bash destrutivos

INPUT=$(cat)
COMMAND=$(echo "$INPUT" | jq -r '.tool_input.command // empty')

# Padrões perigosos
DANGEROUS_PATTERNS=(
    "rm -rf /"
    "rm -rf ~"
    "rm -rf \."
    "> /dev/sda"
    "mkfs\."
    "dd if="
    ":(){:|:&};:"
    "chmod -R 777 /"
    "DROP DATABASE"
    "DROP TABLE"
    "TRUNCATE TABLE"
    "--no-preserve-root"
)

for pattern in "${DANGEROUS_PATTERNS[@]}"; do
    if [[ "$COMMAND" == *"$pattern"* ]]; then
        echo "Bloqueado: comando contém padrão perigoso '$pattern'" >&2
        exit 2
    fi
done

exit 0
