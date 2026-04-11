# Prompt para o Stitch — Hospital Geral do Bengo

> Copia tudo abaixo e cola no Stitch.

---

Cria o design UI/UX completo para um sistema web chamado **"Hospital Geral do Bengo"** (abreviado **HGB**). É um sistema de gestão de filas hospitalares usado num hospital público em Angola. Toda a interface é em **Português**.

O sistema é usado diariamente por 3 tipos de utilizadores: **Recepcionistas**, **Médicos** e **Administradores**. Cada um tem o seu próprio dashboard e funcionalidades específicas. Existe ainda um **Painel TV** público que fica na sala de espera do hospital para os pacientes verem as senhas a serem chamadas.

---

## Como o sistema funciona

1. O **paciente** chega ao hospital e vai à recepção
2. A **recepcionista** regista o paciente no sistema (nome, idade, morada, tipo de consulta) e o sistema gera automaticamente uma **senha** com código sequencial (ex: A001, A002...)
3. Cada senha tem uma **prioridade**: Urgente, Idoso (≥65 anos), Grávida, ou Normal — as urgências são atendidas primeiro
4. O **médico** vê a sua fila no dashboard e clica em **"Chamar Paciente"** — isto faz a senha aparecer no Painel TV da sala de espera
5. O paciente vai ao consultório. O médico pode depois **concluir** o atendimento ou marcar como **ausente** (o paciente não apareceu)
6. O **administrador** monitoriza tudo: estatísticas, relatórios com gráficos, gestão de utilizadores, e logs de auditoria

---

## Os 3 perfis de utilizador

### Recepcionista
- Regista novos pacientes e gera senhas
- Vê a fila de espera em tempo real com métricas (pacientes em espera, urgências activas, atendidos hoje, tempo médio)
- Pesquisa pacientes antigos pelo nome e pode gerar uma nova senha para eles (rechamada)
- Recebe notificações visuais e sonoras quando o médico chama um paciente
- Tem atalhos rápidos: "Repetir último registo" e "Paciente Frequente"

### Médico
- Vê a sua própria fila de pacientes (filtrada pela sua especialidade médica)
- Pode **chamar o próximo paciente**, **concluir atendimento**, ou **marcar como ausente**
- Tem uma barra de "desfazer" de 15 segundos após chamar um paciente (caso tenha clicado por engano)
- Vê o seu consultório atribuído e a especialidade
- Recebe alertas quando existem urgências na fila

### Administrador
- Vê estatísticas gerais do dia: total de senhas, concluídas, ausentes, em espera, tempo médio
- Gráficos de barras por prioridade e por tipo de atendimento
- Tabela dos últimos atendimentos com todos os detalhes
- Gere utilizadores: criar, editar, ver perfil detalhado, activar/desactivar contas
- Logs de auditoria: registo de todas as acções no sistema (login, logout, chamar paciente, concluir, criar utilizador, etc.) com filtros por acção, utilizador e datas
- Relatórios avançados com gráficos (Chart.js): fluxo diário de pacientes, produtividade por médico, horas de pico — com filtro por período e exportação CSV
- Pode ver o perfil e histórico de trabalho de qualquer utilizador

---

## Páginas do sistema

### Página pública
1. **Login** — Formulário de entrada com utilizador e senha. O perfil (Admin/Médico/Recepcionista) é detectado automaticamente pelo sistema após o login.

### Páginas da Recepcionista (3 páginas)
2. **Dashboard** — Métricas em tempo real + fila de espera por prioridade + gráfico mini de fluxo horário + botões de acção rápida + notificação toast quando médico chama paciente
3. **Registar Paciente** — Formulário completo com dados do paciente + selecção visual de prioridade (4 opções com cores) + tipo de atendimento
4. **Pesquisar Pacientes** — Barra de pesquisa por nome + tabela de resultados + painel de detalhe do paciente com histórico de todas as senhas anteriores + formulário de rechamada rápida

### Páginas do Médico (1 página)
5. **Dashboard** — Fila pessoal do médico + card do paciente em atendimento actual (com botões concluir/ausente) + card do próximo paciente a chamar (com botão "Chamar Paciente") + barra de desfazer temporária + fila dos restantes pacientes

### Páginas do Administrador (5 páginas)
6. **Dashboard/Estatísticas** — 5 métricas principais + gráficos de barras horizontais (por prioridade e por tipo) + tabela dos últimos atendimentos
7. **Gestão de Utilizadores** — Tabela com todos os utilizadores do sistema (nome, username, perfil, estado activo/inactivo, data de registo) + botões de acção
8. **Criar/Editar Utilizador** — Formulário para criar ou editar utilizadores (nome, username, senha, perfil, especialidade médica, consultório)
9. **Auditoria** — Tabela de logs de todas as acções no sistema com filtros avançados (por acção, utilizador, período)
10. **Relatórios** — Filtro por datas + 3 gráficos grandes (fluxo diário, produtividade por médico, volume por hora) + exportação CSV

### Páginas partilhadas (todos os perfis)
11. **Mensagens** — Sistema de mensagens internas entre utilizadores. Layout estilo email com lista de mensagens recebidas/enviadas + leitor de mensagem + formulário de composição
12. **Editar Perfil** — Foto de perfil com upload + editar nome + alterar senha
13. **Histórico de Trabalho** — Estatísticas pessoais do utilizador (pacientes atendidos, tempo médio, etc.) + tabela de actividades recentes

### Ecrã público (sem login)
14. **Painel TV (Sala de Espera)** — Ecrã gigante para televisão na sala de espera. Mostra a senha actualmente em atendimento (muito grande, legível a distância), as próximas 3 senhas, o total em espera com tempo médio, as últimas senhas concluídas e as ausentes. Actualiza automaticamente a cada 5 segundos. Tem um relógio digital.

---

## Layout geral

Todas as páginas (excepto Login e Painel TV) partilham o mesmo layout:
- **Sidebar fixa à esquerda** com: logo do hospital, foto/avatar do utilizador logado com nome e cargo, links de navegação, e botão de sair
- **Área de conteúdo principal à direita** com: cabeçalho da página (título + botões de acção), alertas/notificações, e o conteúdo específico de cada página

---

## Dados no sistema

- **Senhas/Tickets**: Código (A001), nome do paciente, idade, morada, peso, tipo de atendimento, prioridade (1-4), estado (espera/chamada/concluída/cancelada), médico atribuído, consultório, hora de criação, hora de chamada
- **Utilizadores**: Nome, username, senha, perfil (admin/medico/recepcionista), foto, especialidade médica (para médicos), consultório, estado (activo/inactivo)
- **Tipos de atendimento**: Consulta Geral, Pediatria, Maternidade, Cirurgia, Oftalmologia, etc.
- **Prioridades**: 1=Urgente (vermelho), 2=Idoso (âmbar), 3=Grávida (roxo), 4=Normal (azul)
- **Auditoria**: Acção, utilizador, detalhes, IP, timestamp

---

## Contexto importante

- Este é um hospital público em **Angola** — os nomes dos pacientes e utilizadores devem ser nomes angolanos/portugueses
- O sistema é usado em turnos de **8-12 horas** — a legibilidade e o conforto visual são fundamentais
- A recepcionista precisa de ver a fila em tempo real sem recarregar a página (usa polling AJAX a cada 10 segundos)
- O painel TV fica a **3 metros de distância** dos pacientes — as senhas precisam de ser enormes e claramente legíveis
- O sistema precisa de ser visualmente consistente entre todas as páginas
- A interface deve sentir-se moderna, profissional e eficiente — como um SaaS premium de healthcare
