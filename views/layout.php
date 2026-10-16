<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Projeto VERITAS</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>

<header class="cabecalho">
    <div class="container cabecalho-conteudo">
        <a href="index.php" class="logo">
            VERITAS
        </a>

        <nav class="menu">
            <a href="index.php?pagina=login">Entrar</a>
            <a href="index.php?pagina=cadastro" class="botao-menu">
                Criar conta
            </a>
        </nav>
    </div>
</header>

<main>
    <?php
        $arquivoPagina = __DIR__ . '/' . $pagina . '.php';

        if (file_exists($arquivoPagina)) {
            require $arquivoPagina;
        } else {
            require __DIR__ . '/home.php';
        }
    ?>
</main>

<footer class="rodape">
    <p>
        Projeto VERITAS — Protótipo acadêmico para apoio à defesa criminal.
    </p>
</footer>

</body>
</html>
