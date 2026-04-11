# Prompt Stitch — Pesquisar Pacientes (Recepção)

> Cola no Stitch:

---

Cria a página de **Pesquisar Pacientes** para o sistema interno do **"Hospital Geral do Bengo"** (HGB). A interface é toda em Português.

Esta página é usada pela Recepcionista para procurar rapidamente um paciente que já foi atendido no passado no hospital. O objectivo é não ter de preencher novamente o formulário inteiro, bastando encontrar o paciente e clicar em "Nova Consulta/Senha".

## Estrutura Geral (Layout)

O layout mantém a mesma arquitectura do sistema:
1. **Sidebar Slim (Esquerda):** Barra lateral fina com ícones. O ícone de lupa ("Pesquisar") deve estar activo.
2. **Header Horizontal (Topo):** Cabeçalho fixo com o nome da secção ("Pesquisar"), o relógio e o perfil.
3. **Área de Conteúdo (Centro):** O espaço de trabalho com a barra de pesquisa e os resultados.

## O que a Área de Conteúdo contém

**Cabeçalho da Página:**
- Texto grande: **"Pesquisar Pacientes"**
- Subtítulo: "Consulte o arquivo ou faça a admissão rápida de um paciente frequente."

**Área de Pesquisa (Topo do conteúdo):**
- Um card limpo e moderno contendo um campo de texto enorme e central de "Pesquisa Inteligente".
- O *placeholder* deve ser: "Pesquise por Nome, Nº de Identificação ou Telefone...".
- Ao lado do campo de texto, um botão grande e primário com um ícone de lupa e o texto "Procurar".

**Estado dos Resultados (Tabela/Lista):**
- Deve apresentar uma lista de estilo "SaaS Data-table" (Tabela de dados premium).
- Título da tabela: **"Resultados encontrados (12 pacientes)"**.
- **Colunas da Tabela:**
  - **Nome Completo** (Texto destacado/negrito)
  - **Identificação** (Subtil / cinzento)
  - **Telefone** (Subtil)
  - **Última Visita** (Data, ex: "Há 2 meses")
  - **Acções** (Alinhado à direita)
- **Botões de Acção em cada linha:**
  - Um botão primário/secundário com ícone de "Relâmpago" ou "+", escrito: **"Admissão Rápida"**. (Serve para saltar o preenchimento de dados e gerar logo uma nova senha).
  - Um botão icónico apenas com um "olho", para "Ver Histórico".

**Empty State (Estado Vazio):**
- Mais em baixo deves desenhar uma ilustração ou ícone de estado vazio (Empty State) bonito e moderno que representaria a página quando a pesquisa não devolve nada ou enquanto não se pesquisa nada.
  - Texto: "Comece a digitar para procurar pacientes" ou "Nenhum paciente encontrado com esse termo".

## Contexto e Design

- Esta página precisa de transmitir velocidade. O campo de pesquisa tem de ser o protagonista absoluto do ecrã mal a página carrega.
- Usa espaçamentos desafogados na tabela para facilitar a leitura rápida.
- O botão de "Admissão Rápida" (que permite saltar directamente para a emissão de senha sem preencher os dados) deve ter um design apelativo, talvez com um estilo `ghost` ou `outline` na cor primária, que muda para preenchido (`solid`) ao passar o rato (hover).
