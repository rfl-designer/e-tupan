# PRD - [Nome do Projeto]

> Baseado em: `dev/SPEC.md`
> Criado em: [DATA]
> Última atualização: [DATA]

## Visão Geral

[Resumo do projeto baseado na especificação. Foco no que será desenvolvido.]

## Objetivos do Produto

1. [Objetivo mensurável 1]
2. [Objetivo mensurável 2]
3. [Objetivo mensurável 3]

## Features

### Feature: Autenticação de Usuários
- **ID**: `auth`
- **Status**: `todo`
- **Prioridade**: Alta
- **Descrição**: Sistema completo de autenticação incluindo login, registro, recuperação de senha e verificação de email.
- **Dependências**: Nenhuma

### Feature: [Nome da Feature]
- **ID**: `feature-id`
- **Status**: `todo`
- **Prioridade**: Alta | Média | Baixa
- **Descrição**: [Descrição detalhada do que a feature faz]
- **Dependências**: [Lista de IDs de features que precisam estar prontas antes]

### Feature: [Nome da Feature]
- **ID**: `feature-id`
- **Status**: `todo`
- **Prioridade**: Alta | Média | Baixa
- **Descrição**: [Descrição detalhada]
- **Dependências**: []

## Status das Features

| Status | Significado |
|--------|-------------|
| `todo` | Não iniciada |
| `in-progress` | Em desenvolvimento |
| `done` | Concluída e testada |

## Métricas de Sucesso

- [ ] [Métrica 1: ex. "100% dos testes passando"]
- [ ] [Métrica 2: ex. "Cobertura de código > 80%"]
- [ ] [Métrica 3: ex. "PHPStan nível 6 sem erros"]
- [ ] [Métrica 4: ex. "Tempo de resposta < 200ms"]

## Cronograma Sugerido

| Fase | Features | Prioridade |
|------|----------|------------|
| MVP | auth, [outras] | Alta |
| V1.1 | [features] | Média |
| V1.2 | [features] | Baixa |

## Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| [Risco 1] | Alta/Média/Baixa | Alto/Médio/Baixo | [Ação] |

## Decisões Técnicas

| Decisão | Justificativa | Data |
|---------|---------------|------|
| [Decisão 1] | [Por que foi decidido assim] | [DATA] |

---

## Próximos Passos

Para cada feature listada:
1. Executar `/laraflow:create-feature <feature-id>`
2. Revisar user stories geradas
3. Desenvolver com `/laraflow:dev-story <feature-id> <story-id>`
