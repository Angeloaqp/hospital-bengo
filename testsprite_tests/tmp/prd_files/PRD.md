# PRD - Sistema de Gestao de Filas Hospitalares

## 1. Visao Geral

O Sistema de Gestao de Filas do Hospital Geral do Bengo e uma aplicacao web interna para organizar o fluxo de pacientes, desde o registo na recepcao ate ao atendimento medico, com apoio a chamadas em painel publico, relatorios administrativos, auditoria e prontuario clinico basico.

## 2. Problema

O hospital precisa reduzir desorganizacao na fila, melhorar a prioridade no atendimento, dar visibilidade em tempo real aos pacientes e permitir que a administracao acompanhe produtividade, tempos de espera e atividade dos utilizadores.

## 3. Objetivos do Produto

- Registar pacientes e emitir senhas com prioridade.
- Permitir que medicos chamem, atendam, concluam ou cancelem senhas.
- Exibir chamadas no painel publico da sala de espera.
- Manter historico de atendimentos e prontuarios basicos.
- Dar ao administrador controlo sobre utilizadores, auditoria e relatorios.
- Melhorar a rastreabilidade das operacoes sensiveis.

## 4. Utilizadores

### Recepcionista
- Regista pacientes.
- Emite senhas.
- Pesquisa pacientes existentes.
- Rechama pacientes para novo atendimento.

### Medico
- Consulta a fila atribuida.
- Chama o proximo paciente.
- Regista notas clinicas, diagnostico e prescricao.
- Conclui ou cancela atendimentos.

### Administrador
- Gere utilizadores.
- Consulta dashboards, relatorios e auditoria.
- Acompanha desempenho por medico, periodo e especialidade.

### Paciente / Publico
- Visualiza no painel publico a senha chamada e proximas senhas.

## 5. Funcionalidades Atuais

- Login por perfil.
- Gestao de sessoes e CSRF.
- Registo de pacientes.
- Emissao de senha por prioridade.
- Fila medica.
- Chamada, conclusao, cancelamento e desfazer chamada.
- Painel publico de chamadas.
- Prontuario clinico basico.
- Mensagens internas.
- Perfil de utilizador com foto e alteracao de senha.
- Gestao de utilizadores.
- Auditoria.
- Relatorios e exportacao CSV.
- Schema e migracoes SQL basicas.

## 6. Escopo da Proxima Evolucao

### Prioridade 1 - Dados do Paciente
- Adicionar BI/NIF, telefone, sexo, data de nascimento e contacto de emergencia.
- Melhorar pesquisa e visualizacao do historico do paciente.

### Prioridade 2 - Triagem
- Criar registo de triagem antes ou durante a emissao da senha.
- Guardar sinais vitais, sintomas, observacoes e prioridade clinica.
- Mostrar dados de triagem ao medico.

### Prioridade 3 - Prontuario Melhorado
- Separar notas clinicas, diagnostico, prescricao, exames e recomendacoes.
- Permitir consulta rapida do historico clinico do paciente.

### Prioridade 4 - Administracao Operacional
- Criar gestao de especialidades, consultorios e tipos de atendimento.
- Melhorar associacao entre medico, consultorio e especialidade.

## 7. Fora de Escopo Inicial

- Integracao com sistemas externos do Ministerio da Saude.
- Marcacao online por pacientes.
- App mobile nativa.
- Pagamentos, faturacao ou seguros.
- Assinatura digital de documentos clinicos.

## 8. Requisitos Funcionais

- O sistema deve impedir acesso a telas sem login.
- O sistema deve respeitar permissoes por perfil.
- O sistema deve emitir senhas ordenadas por prioridade e hora de chegada.
- O medico deve ver apenas a fila aplicavel ao seu contexto.
- O painel publico deve atualizar chamadas sem recarregamento manual frequente.
- Todas as acoes sensiveis devem ser auditaveis.
- O administrador deve conseguir consultar relatorios por periodo.

## 9. Requisitos Nao Funcionais

- Compatibilidade com PHP 8.2 e XAMPP.
- Base de dados MySQL/MariaDB.
- Interface responsiva para desktop e mobile.
- Sem dependencias externas obrigatorias alem de assets CDN ja usados.
- Codigo simples, mantendo o padrao MVC atual.
- Uso de prepared statements para acesso a dados.
- Validacao server-side em formularios criticos.

## 10. Metricas de Sucesso

- Reducao do tempo medio de espera.
- Menos senhas perdidas ou duplicadas.
- Maior rastreabilidade dos atendimentos.
- Uso regular dos relatorios pela administracao.
- Menor dependencia de registos manuais em papel.

## 11. Riscos

- Dados clinicos exigem cuidado com privacidade e permissoes.
- Evolucao do schema precisa manter compatibilidade com instalacoes existentes.
- O fluxo real do hospital pode exigir ajustes apos testes com utilizadores.
- Dependencia de XAMPP/local server pode limitar implantacao em producao.

## 12. Roadmap Resumido

1. Consolidar ficha do paciente.
2. Implementar triagem estruturada.
3. Melhorar prontuario clinico.
4. Criar gestao operacional de especialidades e consultorios.
5. Expandir relatorios administrativos.
6. Reforcar seguranca, validacoes e testes manuais por perfil.
