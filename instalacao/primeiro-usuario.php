<?php
// Execute somente no terminal. Este arquivo não precisa ir para a hospedagem.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function ler(string $rotulo): string
{
    echo $rotulo . ': ';
    return rtrim((string) fgets(STDIN), "\r\n");
}

$nome = trim(ler('Nome'));
$usuario = trim(ler('Usuário'));
$senha = ler('Senha (mínimo de 6 caracteres; visível enquanto digita)');
function tamanho(string $texto): int
{
    $quantidade = preg_match_all('/./us', $texto);
    return $quantidade === false ? PHP_INT_MAX : $quantidade;
}
if ($nome === '' || tamanho($nome) > 100 || $usuario === '' || tamanho($usuario) > 50
    || tamanho($senha) < 6 || tamanho($senha) === PHP_INT_MAX || strlen($senha) > 72 || strpos($senha, "\0") !== false) {
    fwrite(STDERR, "Confira os dados e tente novamente.\n");
    exit(1);
}

// Valores em hexadecimal evitam erros com acentos, apóstrofos ou aspas no SQL.
$campos = array_map(
    fn ($texto) => 'CONVERT(0x' . bin2hex($texto) . ' USING utf8mb4)',
    [$nome, $usuario, password_hash($senha, PASSWORD_DEFAULT)]
);
echo 'INSERT INTO usuarios (nome, usuario, senha) VALUES (' . implode(', ', $campos) . ");\n";
