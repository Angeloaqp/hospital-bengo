# Hospital Geral do Bengo - Sistema de Gestão de Filas

Sistema desenvolvido em PHP 8.2 para gestão de filas hospitalares, incluindo:
- Registo de pacientes com triagem de prioridade.
- Painel público para sala de espera em tempo real.
- Dashboard médico para atendimento e chamadas.
- Painel de administração com estatísticas e gestão de utilizadores.

## Tecnologias
- PHP 8.2 + PDO
- MySQL / MariaDB
- Vanilla CSS + JavaScript

## Instalação
1. Clone o repositório para o seu servidor web (ex: `htdocs` do XAMPP).
2. Importe o ficheiro SQL localizado em `database/schema.sql`.
3. Configure os detalhes da base de dados em `config/database.php`.
4. Aceda via browser (ex: `http://localhost/hospital-bengo`).

## Utilizadores Padrão
- Admin: `admin` / `admin123`
- Médico: `dr_silva` / `medico123`
- Recepcionista: `ana_rececao` / `senha123`
