# Prompt Stitch — Página de Login

> Cola no Stitch:

---

Cria a página de **Login** para o sistema **"Hospital Geral do Bengo"** (HGB) — um sistema de gestão de filas de um hospital público em Angola. A interface é toda em Português.

Esta é a **primeira página que qualquer utilizador vê** ao abrir o sistema. É a porta de entrada para recepcionistas, médicos e administradores do hospital. Não é um site público — é uma ferramenta interna de trabalho usada diariamente pelo pessoal do hospital.

## O que a página contém

**Cabeçalho / Marca do hospital:**
- O logotipo abreviado do hospital: **"HGB"**
- O nome completo: **"Hospital Geral do Bengo"**
- A descrição do sistema: **"Sistema de Gestão de Filas Hospitalares"**

**Formulário de login com 3 campos:**
- **Utilizador** — campo de texto onde o funcionário escreve o seu nome de utilizador (placeholder: "O seu nome de utilizador")
- **Senha** — campo de password (placeholder: "A sua senha")
- **Perfil de acesso** — um campo dropdown desactivado (não editável) que mostra o texto "Detectado automaticamente pelo sistema". Existe apenas para informar o utilizador que o sistema vai identificar automaticamente se ele é admin, médico ou recepcionista após o login

**Botão de submissão:**
- Texto: **"Entrar no sistema"**

**Mensagem de erro:**
- Quando as credenciais estão erradas, aparece uma mensagem de erro acima do formulário (ex: "Utilizador ou senha incorrectos.")

**Rodapé:**
- Pequeno texto: **"Hospital Geral do Bengo v2.0 — Acesso restrito a pessoal autorizado"**

## Contexto importante

- A página é **fullscreen** — não tem sidebar nem navegação. É apenas o formulário de login centralizado
- É usada por 3 tipos de utilizador: **Recepcionistas** (registam pacientes), **Médicos** (chamam e atendem pacientes), e **Administradores** (gerem o hospital)
- O hospital é em Angola — o design deve transmitir confiança, profissionalismo e seriedade institucional
- A página precisa de funcionar bem tanto em ecrãs de 1920px (desktops da recepção) como em portáteis de 1366px
