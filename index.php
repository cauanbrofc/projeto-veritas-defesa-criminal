<?php

// Guarda temporariamente o HTML antes de enviá-lo ao navegador.
// Isso permite que o PHP faça redirecionamentos corretamente.
ob_start();

session_start();


// Verifica qual página foi solicitada.
// Se nenhuma página for informada, será aberta a página inicial.
$pagina = $_GET['pagina'] ?? 'home';

// Se o usuário clicar em "sair", encerraremos a sessão.
if ($pagina === 'sair') {
    session_unset();
    session_destroy();

    header('Location: index.php?pagina=login');
    exit;
}

// Lista de páginas existentes no sistema.
$paginasPermitidas = [
    'home',
    'login',
    'cadastro',
    'painel',
    'casos',
    'caso',
    'pessoas'
];


// Se a página informada não estiver na lista,
// o sistema abrirá a página inicial.
if (!in_array($pagina, $paginasPermitidas, true)) {
    $pagina = 'home';
}

// O painel somente poderá ser acessado por um usuário logado.
$paginasProtegidas = [
    'painel',
    'casos',
    'caso',
    'pessoas'
];

if (
    in_array($pagina, $paginasProtegidas, true)
    && !isset($_SESSION['usuario_id'])
) {
    header('Location: index.php?pagina=login');
    exit;
}


// Carrega o layout principal.
require __DIR__ . '/views/layout.php';


