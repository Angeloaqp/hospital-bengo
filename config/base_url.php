<?php
// ================================================
// Hospital Geral do Bengo
// URL base do sistema
// ================================================
// Override via variável de ambiente (opcional):
//   HB_BASE_URL — ex: http://meuservidor.ao/hospital-bengo/
// Se não definida, usa o valor padrão do XAMPP local.
// ================================================

define('BASE_URL', getenv('HB_BASE_URL') ?: 'http://localhost/hospital-bengo/');
define('APP_NOME', 'Hospital Geral do Bengo');
define('APP_VERSAO', '1.1');
