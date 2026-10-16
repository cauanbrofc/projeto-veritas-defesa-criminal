<?php

declare(strict_types=1);

/**
 * Conexão com o banco de dados MySQL do Projeto VERITAS.
 */

function conectarBanco(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $porta = '3306';
    $banco = 'veritas';
    $usuario = 'root';

    /*
     * Se o seu MySQL Workbench possui senha para o usuário root,
     * substitua o valor vazio pela senha configurada.
     */
    $senha = '';

    $dsn = "mysql:host={$host};port={$porta};dbname={$banco};charset=utf8mb4";

    $opcoes = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND =>
            "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO(
            $dsn,
            $usuario,
            $senha,
            $opcoes
        );

        return $pdo;
    } catch (PDOException $erro) {
        error_log(
            'Erro de conexão com o banco VERITAS: ' . $erro->getMessage()
        );

        die(
            'Não foi possível conectar ao banco de dados. '
            . 'Verifique as configurações do MySQL.'
        );
    }
}
