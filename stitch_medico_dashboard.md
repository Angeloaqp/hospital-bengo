# Prompt Stitch — Fila Actual (Médico)

> Cola no Stitch:

---

Cria a página de **Dashboard do Médico (Fila Actual)** para o sistema interno do **"Hospital Geral do Bengo"** (HGB). A interface é toda em Português.

Esta é a página de trabalho diário dos médicos dentro do consultório. A função principal desta interface é permitir ao médico focar-se em quem está a atender agora e quem vem a seguir, de forma totalmente clara e sem distracções.

## Estrutura Geral (Layout)

O layout mantém a mesma arquitectura das restantes páginas:
 . O ícone de lista/pessoas ("Fila Actual") deve estar activo.
 Cabeçalho fixo com o título "Dashboard". À direita, um relógio em tempo real, um "Badge" indicando a Sala (ex: "📍 Consultório 2") e o perfil do médico.
3. **Área de Conteúdo (Centro):** O espaço de trabalho limpo e focado no atendimento.

## O que a Área de Conteúdo contém

**Cabeçalho da Página:**
- Texto grande: **"Fila de Atendimento"**
- Subtítulo: "Dr. Augusto Cândido — Clínica Geral — 4 pacientes em espera"

**Alerta Crítico (Opcional, visível):**
- Caso haja urgências: **"⚡ Tem 1 urgência activa na sua fila."** (Um banner de aviso/alarde moderado para garantir que o médico nota).

**Layout Focado no Atendimento (Topo):**
Cria um grande bloco em grande destaque (um card hero ou dois cards lado a lado) que mostra a acção imediata:

1. **Card Principal: "A Atender Agora"** (Destacado, ex: borda primária ou fundo ligeiramente colorido):
   - Status: "Em Consulta"
   - Dados principais: Senha gigante (ex: **N-045**), Nome do paciente largo ("João Silva"), Idade ("34 anos").
   - Botões de Acção do Atendimento:
     - Botão gigante Verde/Primário: **"Concluir Atendimento"**
     - Botão subtil/Secundário: **"Ausente / Cancelar"**

2. **Card Secundário: "Próximo Paciente"** (Menos destacado que o em atendimento, mas visível):
   - Dados: Senha (**P-012**), Nome ("Maria Mendes").
   - Um botão vibrante e chamativo (ex: azul claro ou laranja se for prioritário): **"🔊 Chamar Próximo"** (Este botão actualiza o mostrador lá fora na sala de espera).

*(Nota de UI: Se não houver ninguém "Em Consulta", o bloco "Próximo Paciente" deve ganhar o destaque principal para o médico clicar em "Chamar").*

**Tabela: Restante Fila de Espera (Abaixo):**
- Uma lista SaaS limpa dos restantes pacientes que aguardam por este médico.
- Título: **"Restantes pacientes na fila (3)"**
- Tabela simples:
  - **Senha** (Identificador visual)
  - **Nome Formato**
  - **Prioridade** (Badges / Chips modernos com cores de semáforo: Urgente, Idoso, Normal)
  - **Tempo de Espera** (ex: "Há 40 min", realçado a vermelho se for superior a 1 hora)

## Contexto e Design

- A UX para o médico tem de ser incrivelmente fluída. Os botões de **"Chamar"** e **"Concluir"** são as acções mais repetidas do dia, por isso devem ter "Active States" (quando se clica neles) muito presentes, com micro-interacções como redução do botão (scale ou press) e uma pequena sombra (glow).
- Mantém grande contraste nas senhas e nos nomes das pessoas para lerem depressa.
- Transmite um tom clínico, higiénico e imaculado. Muito espaço em branco (whitespace), sombras suaves e cantos arredondados, evitando ruído visual que possa cansar a vista após turnos de 12 horas.
