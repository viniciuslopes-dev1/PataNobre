<?php
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'veterinaria';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $conexao;
    if (!$conexao) {
        $conexao = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $conexao;
}

function logged_in(): bool
{
    return isset($_SESSION['usuario']);
}

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
