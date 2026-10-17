<?php

/*
 * Esta página mostra os detalhes de um caso específico.
 *
 * O caso será buscado pelo ID recebido na URL:
 *
 * index.php?pagina=caso&id=1
 */

// Inclui a conexão com o banco.
require_once __DIR__ . '/../config/conexao.php';

// Recupera o ID do advogado que está logado.
$usuarioId = $_SESSION['usuario_id'];

// Recupera o ID do caso enviado pela URL.
$casoId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Se o ID não for válido, voltamos para a lista de casos.
if (!$casoId) {
    header('Location: index.php?pagina=casos');
    exit;
}

try {
    // Abre a conexão com o banco.
    $pdo = conectarBanco();

    /*
     * Busca o caso pelo ID.
     *
     * A condição usuario_id é essencial:
     * ela impede que um usuário veja o caso de outra conta.
     */
    $consultaCaso = $pdo->prepare(
        'SELECT
            id,
            titulo,
            descricao,
            status,
            criado_em,
            atualizado_em
         FROM casos
         WHERE id = :caso_id
           AND usuario_id = :usuario_id
         LIMIT 1'
    );

    $consultaCaso->execute([
        ':caso_id' => $casoId,
        ':usuario_id' => $usuarioId
    ]);

    // Recupera os dados do caso.
    $caso = $consultaCaso->fetch();

    /*
     * Se o caso não existir ou não pertencer ao usuário,
     * ele não será exibido.
     */
    if (!$caso) {
        http_response_code(404 );

        echo '<section class="pagina-erro">';
        echo '<div class="container">';
        echo '<h1>Caso não encontrado</h1>';
        echo '<p>O caso não existe ou não pertence à sua conta.</p>';
        echo '<a href="index.php?pagina=casos" class="botao-principal">';
        echo 'Voltar para meus casos';
        echo '</a>';
        echo '</div>';
        echo '</section>';

        return;
    }
} catch (PDOException $erro) {
    error_log(
        'Erro ao consultar detalhes do caso: '
        . $erro->getMessage()
    );

    $caso = null;
}

/*
 * Converte o status do banco para um texto amigável.
 */
function nomeStatusCaso(string $status): string
{
    $statusPermitidos = [
        'aberto' => 'Aberto',
        'em_revisao' => 'Em revisão',
        'arquivado' => 'Arquivado'
    ];

    return $statusPermitidos[$status] ?? 'Não informado';
}

/*
 * Escolhe a classe visual correspondente ao status.
 */
function classeStatusCaso(string $status): string
{
    $classesStatus = [
        'aberto' => 'status-ativo',
        'em_revisao' => 'status-revisao',
        'arquivado' => 'status-arquivado'
    ];

    return $classesStatus[$status] ?? 'status-arquivado';
}
?>

<?php if ($caso): ?>

<section class="pagina-detalhes-caso">
    <div class="container">

        <div class="detalhes-caso-navegacao">
            <a
                href="index.php?pagina=casos"
                class="link-voltar"
            >
                ← Voltar para meus casos
            </a>
        </div>

        <div class="cabecalho-detalhes-caso">
            <div>
                <span class="etiqueta">
                    DOSSIÊ #<?= (int) $caso['id'] ?>
                </span>

                <h1>
                    <?= htmlspecialchars(
                        $caso['titulo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h1>

                <p>
                    Caso cadastrado em
                    <?= date(
                        'd/m/Y H:i',
                        strtotime($caso['criado_em'])
                    ) ?>
                </p>
            </div>

            <span class="<?= htmlspecialchars(
                classeStatusCaso($caso['status']),
                ENT_QUOTES,
                'UTF-8'
            ) ?>">
                <?= htmlspecialchars(
                    nomeStatusCaso($caso['status']),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </div>

        <div class="detalhes-caso-grid">

            <section class="resumo-caso-card">
                <span class="etiqueta">RESUMO DO CASO</span>

                <h2>Informações principais</h2>

                <?php if (
                    $caso['descricao'] !== null
                    && $caso['descricao'] !== ''
                ): ?>
                    <p class="descricao-caso">
                        <?= nl2br(
                            htmlspecialchars(
                                $caso['descricao'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ) ?>
                    </p>
                <?php else: ?>
                    <p class="descricao-caso texto-sem-descricao">
                        Este caso ainda não possui uma descrição.
                    </p>
                <?php endif; ?>

                <div class="informacoes-caso">
                    <div>
                        <span>Identificação</span>
                        <strong>
                            CASO #<?= (int) $caso['id'] ?>
                        </strong>
                    </div>

                    <div>
                        <span>Status atual</span>
                        <strong>
                            <?= htmlspecialchars(
                                nomeStatusCaso($caso['status']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <span>Última atualização</span>
                        <strong>
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($caso['atualizado_em'])
                            ) ?>
                        </strong>
                    </div>
                </div>
            </section>

            <section class="modulos-caso">
                <a
                href="index.php?pagina=pessoas&caso_id=<?= (int) $caso['id'] ?>"
                class="modulo-caso modulo-link"
            >
                <span class="modulo-numero">01</span>

                <div>
                    <h3>Pessoas</h3>

                    <p>
                        Participantes relacionados ao caso.
                    </p>
                </div>

                <span class="modulo-status">
                    Acessar
                </span>
                </a>


                <div class="modulo-caso">
                    <span class="modulo-numero">02</span>
                    <div>
                        <h3>Locais</h3>
                        <p>
                            Locais associados aos registros.
                        </p>
                    </div>
                    <span class="modulo-status">Em breve</span>
                </div>

                <div class="modulo-caso">
                    <span class="modulo-numero">03</span>
                    <div>
                        <h3>Acontecimentos</h3>
                        <p>
                            Fatos organizados por data e horário.
                        </p>
                    </div>
                    <span class="modulo-status">Em breve</span>
                </div>

                <div class="modulo-caso">
                    <span class="modulo-numero">04</span>
                    <div>
                        <h3>Evidências</h3>
                        <p>
                            Registros e arquivos relacionados.
                        </p>
                    </div>
                    <span class="modulo-status">Em breve</span>
                </div>

                <div class="modulo-caso">
                    <span class="modulo-numero">05</span>
                    <div>
                        <h3>Linha do tempo</h3>
                        <p>
                            Visualização cronológica do caso.
                        </p>
                    </div>
                    <span class="modulo-status">Em breve</span>
                </div>
            </section>

        </div>

    </div>
</section>

<?php endif; ?>
