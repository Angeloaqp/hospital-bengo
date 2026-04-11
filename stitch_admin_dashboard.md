# Prompt Stitch — Dashboard de Administração (Estatísticas)

> Cola no Stitch:

---

Cria o **Dashboard de Administração** para o sistema interno do **"Hospital Geral do Bengo"** (HGB). A interface é toda em Português.

Esta é a página usada pela Direcção Clínica e Gestores do Hospital para ter uma visão geral do desempenho diário, verificar gargalos no atendimento e analisar os tipos de serviço mais solicitados. O tom deve ser corporativo, analítico e extremamente premium, tal como uma ferramenta de Business Intelligence moderna.

## Estrutura Geral (Layout)

Mantém a arquitectura do sistema:
Ícone de "Gráfico de Barras / Dashboard" deve estar activo. Mais abaixo estão ícones como Utilizadores, Especialidades e Definições. 
 Cabeçalho com título "Dashboard", o relógio, botão de imprimir relatório e o avatar do "Gestor".
**Área de Conteúdo (Centro):** O espaço principal de trabalho, que será rico em dados visuais.

## O que a Área de Conteúdo contém

**Cabeçalho da Página:**
- Título grande: **"Estatísticas de Atendimento"**
- Subtítulo: "Visão Geral Operacional — Hoje"
- Pode incluir um *Dropdown/Filtro* discreto "Data: Hoje" à direita do título.

**Secção 1: Indicadores Chave de Desempenho (KPIs)**
- Uma grelha (`grid`) no topo com **5 Cartões de Métrica** modernos:
  - **Total Entradas** (ex: 245) - Cor Neutra/Primária
  - **Concluídos** (ex: 198) - Destaque Verde
  - **Ausentes / Cancelados** (ex: 12) - Destaque Vermelho Suave
  - **Actualmente em Espera** (ex: 35) - Destaque Azul ou Laranja
  - **Tempo Médio de Espera** (ex: 42m) - Neutro

*Nota de Design para os KPIs: Devem ter pequenos gráficos de tendência tipo "sparkline" no fundo, ou uma indicação "+5% que ontem" em texto verde muito pequeno.*

**Secção 2: Gráficos de Análise (Layout 2 Colunas)**
- Divide o espaço abaixo em 2 grandes Cartões (Painéis) brancos, limpos, com margens desafogadas.

  - **Cartão Esquerdo: "Atendimento por Prioridade"**
    - Apresenta um gráfico de barras horizontais muito refinado.
    - Categorias: Urgência, Idoso, Grávida, Normal.
    - Barras com cantos ligeiramente arredondados na extremidade, usando a paleta de cores correspondente à urgência (Vermelho, Laranja, Roxo, Azul). 
    - Incluir o número/percentagem no final de cada barra.

  - **Cartão Direito: "Carga por Especialidade / Serviço"**
    - Um Gráfico em formato "Doughnut" (Rosquinha) ou Barras Verticais limpas.
    - Categorias: Clínica Geral (45%), Urgência/Emergência (30%), Pediatria (15%), Maternidade (10%).
    - Não desenhes um gráfico pesado, faz algo super leve visualmente, estilo Apple Health ou Stripe Dashboard.

**Secção 3: Estado do Sistema (Tabela de Operação)**
- Um painel inferior a meio tamanho ou largura inteira: **"Estado dos Serviços (Live)"**
- Uma tabela com 4-5 linhas sobre os serviços abertos:
  - Serviço, Gabinetes Abertos, Lotação/Fase, Alerta.
  - Ex: "Pediatria" | "2 Gabinetes" | "Atrasos reportados (tempo > 1h)" | *Status Pill* Vermelha.
  - Ex: "Clínica Geral" | "4 Gabinetes" | "Fila fluída" | *Status Pill* Verde.

## Contexto e Design

- Isto é uma ferramenta analítica de hospitalização. Nenhuma distracção é permitida, os fundos de secção devem ser discretos (brancos e off-whites).
- Usa sobras profundas mas super dispersas (`shadow-sm` do Tailwind) para elevar os paneis do fundo.
- Cuidado com o espaço de respiração (`padding`). A beleza das dashboards puras reside no espaço em branco à volta dos dados.
