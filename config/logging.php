<?php

// ============== CONFIGURAÇÃO DE LOG (MONOLOG) ==============

use Monolog\Level;
use Monolog\Processor\WebProcessor;

// Determina o nível mínimo de log com base no ambiente
$env = $_ENV['APP_ENV'] ?? 'production';
$levelMap = [
    'development' => Level::Debug,
    'testing'     => Level::Debug,
    'production'  => Level::Warning,
];
$logLevel = $levelMap[$env] ?? Level::Warning;

return [
    /*
    |--------------------------------------------------------------------------
    | Canal (Channel)
    |--------------------------------------------------------------------------
    | Nome do canal de log. Identifica a origem dos logs (ex: app, auth, api).
    */
    'channel' => 'app',

    /*
    |--------------------------------------------------------------------------
    | Caminho do arquivo de log
    |--------------------------------------------------------------------------
    | Caminho absoluto para o arquivo de log. O Monolog criará arquivos rotativos
    | com base neste nome (ex: app-2026-06-18.log).
    */
    'path' => ROOT_DIR . '/storage/logs/app.log',

    /*
    |--------------------------------------------------------------------------
    | Rotação de arquivos (dias de retenção)
    |--------------------------------------------------------------------------
    | Número de dias que os arquivos de log serão mantidos. Arquivos mais antigos
    | serão excluídos automaticamente.
    */
    'days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Nível mínimo de log
    |--------------------------------------------------------------------------
    | Define o nível mínimo para registrar logs. Apenas mensagens com nível
    | igual ou superior a este serão armazenadas.
    */
    'level' => $logLevel,

    /*
    |--------------------------------------------------------------------------
    | Processadores (Processors)
    |--------------------------------------------------------------------------
    | Classes que enriquecem cada registro de log com dados adicionais.
    | Ex: WebProcessor adiciona IP, URI, método HTTP, user agent.
    */
    'processors' => [
        WebProcessor::class,
        // Adicione outros processadores aqui se necessário
    ],
];