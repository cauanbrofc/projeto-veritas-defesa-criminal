<?php

// Recupera o nome salvo na sessão.
$nomeUsuario = $_SESSION['usuario_nome'] ?? 'Advogado';

// Protege a exibição do nome na tela.
$nomeUsuarioSeguro = htmlspecialchars(
    $nomeUsuario,
    ENT_QUOTES,
    'UTF-8'
);
?>

<section class="painel">
    <div class="container">

        <div class="painel-cabecalho">
            <div>
                <span class="etiqueta">AMBIENTE DE TRABALHO</span>

                <h1>
                    Bem-vindo,
                    <span><?= $nomeUsuarioSeguro ?>.</span>
                </h1>

                <p>
                    Este é o painel inicial do Projeto VERITAS.
                    A partir daqui, você poderá organizar seus casos.
                </p>
            </div>

            <a
                href="index.php?pagina=sair"
                class="botao-secundario"
            >
                Sair do sistema
            </a>
        </div>

        <div class="painel-aviso">
            <strong>Protótipo acadêmico</strong>

            <p>
                Utilize somente dados fictícios ou simulados.
                O Projeto VERITAS não substitui a análise profissional
                do advogado e não possui integração com órgãos oficiais.
            </p>
        </div>

        <div class="painel-grid">

            <article class="painel-card">
                <span class="recurso-numero">01</span>

                <h2>Meus casos</h2>

                <p>
                    Cadastre e consulte os casos organizados
                    pelo advogado autenticado.
                </p>

                <a href="index.php?pagina=casos" class="link-indisponivel">
                    Acessar casos
                </a>
            </article>


            <article class="painel-card">
                <span class="recurso-numero">02</span>

                <h2>Linha do tempo</h2>

                <p>
                    Visualize acontecimentos e evidências
                    em ordem cronológica.
                </p>

            <a href="index.php?pagina=casos" class="link-indisponivel">
                Acessar casos
            </a>

            </article>

            <article class="painel-card">
                <span class="recurso-numero">03</span>

                <h2>Alertas</h2>

                <p>
                    Consulte indicações de possíveis conflitos
                    para revisão profissional.
                </p>

                <a href="#" class="link-indisponivel">
                    Em breve
                </a>
            </article>

        </div>

    </div>
</section>
