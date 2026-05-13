# Hospital Geral do Bengo — Sistema de Gestão de Filas

Sistema desenvolvido em PHP 8.2 para gestão de filas hospitalares, incluindo:
- Registo de pacientes com triagem de prioridade.
- Painel público para sala de espera em tempo real.
- Dashboard médico para atendimento, prontuários e chamadas.
- Painel de administração com estatísticas, relatórios e gestão de utilizadores.
- Sistema de mensagens internas e auditoria.

## Tecnologias

- **Backend:** PHP 8.2 + PDO (sem frameworks)
- **Base de dados:** MySQL 8.x / MariaDB 10.4+
- **Frontend:** Tailwind CSS (CDN) + JavaScript vanilla
- **Servidor local:** XAMPP recomendado

## Requisitos

- PHP 8.2 ou superior (com extensões `pdo_mysql`, `fileinfo`, `mbstring`)
- MySQL 8.x ou MariaDB 10.4+
- Servidor web Apache (incluído no XAMPP)
- Módulo `mod_rewrite` activo (opcional, para URLs limpas)

## Instalação (nova)

1. Clone ou copie o projecto para a pasta `htdocs` do XAMPP:
   ```
   C:\xampp\htdocs\hospital-bengo\
   ```

2. Crie a base de dados no phpMyAdmin ou terminal MySQL:
   ```sql
   CREATE DATABASE hospital_bengo
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```

3. Importe o schema completo:
   ```
   mysql -u root hospital_bengo < database/schema.sql
   ```
   Ou via phpMyAdmin: seleccione a base `hospital_bengo` → aba "Importar" → carregue `database/schema.sql`.

4. Verifique que as credenciais em `config/database.php` correspondem ao seu ambiente.
   Por omissão: `root` sem password (padrão XAMPP).

5. Certifique-se de que a pasta de uploads existe e tem permissões de escrita:
   ```
   public/uploads/fotos/
   ```

6. Inicie o Apache e MySQL no painel do XAMPP.

7. Aceda via browser:
   ```
   http://localhost/hospital-bengo/public/index.php
   ```

## Utilizadores Padrão

| Perfil        | Username   | Password         |
|---------------|------------|------------------|
| Administrador | `admin`    | `Hospital@2025`  |
| Médico        | `medico`   | `Hospital@2025`  |
| Recepcionista | `recepcao` | `Hospital@2025`  |

> **Nota:** Altere as passwords após o primeiro login em produção.

## Variáveis de Ambiente (opcional)

Para ambientes diferentes do XAMPP local, pode definir variáveis de ambiente
em vez de editar os ficheiros de configuração:

| Variável        | Descrição                    | Valor padrão                          |
|-----------------|------------------------------|---------------------------------------|
| `HB_BASE_URL`   | URL base da aplicação        | `http://localhost/hospital-bengo/`    |
| `HB_DB_HOST`    | Host da base de dados        | `localhost`                           |
| `HB_DB_USER`    | Utilizador da BD             | `root`                               |
| `HB_DB_PASS`    | Password da BD               | *(vazio)*                             |
| `HB_DB_NAME`    | Nome da base de dados        | `hospital_bengo`                      |

Exemplo no Apache (`httpd.conf` ou `.htaccess`):
```
SetEnv HB_DB_HOST 192.168.1.100
SetEnv HB_DB_PASS minha_senha_segura
```

Se nenhuma variável estiver definida, o sistema usa os valores padrão do XAMPP.

## Migrações da Base de Dados

### Instalação nova
Usar apenas `database/schema.sql`. Já contém toda a estrutura e dados iniciais.

### Instalação existente (actualização)
Aplicar os ficheiros SQL da pasta `database/migrations/` por ordem numérica:

```
mysql -u root hospital_bengo < database/migrations/001_prontuarios_e_bloqueio.sql
```

Cada migração é idempotente (usa `IF NOT EXISTS` / `IF EXISTS`) e pode ser
re-executada sem risco. Verifique o cabeçalho de cada ficheiro para saber
a que versão se aplica.

### Regras de evolução
- **Instalação nova** → importar `schema.sql` (sempre actualizado).
- **Base existente** → aplicar migrações pendentes por ordem.
- **Novas migrações** → criar ficheiro `NNN_descricao.sql` na pasta `database/migrations/`.

## Validação da Instalação

1. **Testar ligação à BD:**
   ```
   C:\xampp\php\php.exe -r "require 'config/database.php'; echo Database::ligar() ? 'DB_OK' : 'DB_FAIL';"
   ```

2. **Testar acesso HTTP (com Apache activo):**
   ```
   curl -I http://localhost/hospital-bengo/public/index.php
   ```
   Espera-se: `HTTP/1.1 200 OK`

3. **Verificar PHP lint em ficheiro específico:**
   ```
   C:\xampp\php\php.exe -l app/controllers/perfil.php
   ```

## Estrutura do Projecto

```
hospital-bengo/
├── app/
│   ├── controllers/    # Lógica de negócio (POST handlers)
│   ├── models/         # Acesso a dados (PDO prepared statements)
│   └── views/          # Interface (PHP + Tailwind)
│       ├── admin/      # Painel de administração
│       ├── comum/      # Componentes partilhados (header, sidebar)
│       ├── medico/     # Dashboard médico
│       ├── perfil/     # Gestão de perfil pessoal
│       └── recepcionista/  # Fila e registo de pacientes
├── config/             # Configurações (BD, sessão, segurança)
├── database/
│   ├── schema.sql      # Schema completo (instalação nova)
│   └── migrations/     # Migrações incrementais
├── public/             # Ficheiros públicos (CSS, JS, uploads)
│   ├── index.php       # Página de login
│   └── painel.php      # Painel público de chamadas
└── scripts/            # Scripts auxiliares (cron, manutenção)
```
