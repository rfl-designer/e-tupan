# Design log — site institucional Tupan

Ciclo de iteração e crítica para melhorar o site institucional: `/`, `/sobre`, `/contato`, `/blog`, `/blog/{slug}` e `/solucoes/{slug}`.

## Papéis

- **Juiz**: Opus, esforço medium. Avalia só o site renderizado (screenshots e relatório de auditoria) e o código. Não vê o raciocínio do Iterador nem este log. Não edita código.
- **Iterador**: Sonnet, esforço medium. Aplica até 5 mudanças por rodada, na ordem de prioridade do Juiz.

## Rubrica

| Critério | Peso |
|---|---|
| Clareza da proposta de valor (primeiros 5 segundos) | 20% |
| Textos: concisão, tom, CTAs | 15% |
| Hierarquia visual e tipografia | 15% |
| Consistência (cores, espaçamento, componentes) | 15% |
| Micro interações (hover, foco, transições, feedback) | 10% |
| Responsividade (390px, 768px, 1440px) | 10% |
| Acessibilidade (contraste AA, foco visível, alt, semântica) | 10% |
| Performance percebida (peso de imagens, animações leves) | 5% |

Nota final = média ponderada, calculada pelo orquestrador a partir das notas por critério.

## Critério de parada

- Nota final ≥ 9,0 e nenhum critério abaixo de 8, em 2 rodadas seguidas.
- Ou 8 rodadas.
- Ou nota sem subir em 3 rodadas seguidas (bloqueio relatado).

Uma rodada que baixa a nota é revertida com `git revert`, e o Iterador tenta outra abordagem.

## Ambiente e ferramentas

- Herd e Postgres estavam parados nesta sessão. O site roda num servidor PHP local em `http://127.0.0.1:8765`, com sessão e cache em arquivo e sem banco. As páginas institucionais leem o conteúdo de `config/institutional.php`.
- Captura e auditoria: `capture.mjs`, fora do repositório (scratchpad da sessão). Usa `playwright-core` 1.56.1 com o Chrome instalado e axe-core 4.14.0 (WCAG 2.2 AA). Mede foco por teclado, links, peso de página e `prefers-reduced-motion`. Nenhuma dependência nova no projeto.
- Larguras: 390, 768 e 1440 px.

## Rodadas
