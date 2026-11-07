<?php

/*
 * Módulo de acontecimentos.
 *
 * Este arquivo permite:
 *
 * 1. Verificar se o caso pertence ao usuário logado;
 * 2. Buscar os locais disponíveis para o caso;
 * 3. Cadastrar um acontecimento;
 * 4. Listar os acontecimentos em ordem cronológica.
 */

// Inclui o arquivo de conexão com o banco.
require_once __DIR__ . '/../config/conexao.php';

// Recupera o ID do usuário logado.
$usuarioId = $_SESSION['usuario_id'];

// Recupera o ID do caso pela URL.
$casoId = filter_input(
    INPUT_GET,
    'caso_id',
    FILTER_VALIDATE_INT
);

// Se o ID for inválido, volta para a lista de casos.
if (!$casoId) {
    header('Location: index.php?pagina=casos');
    exit;
}

// Variáveis para mensagens.
$mensagemSucesso = '';
$mensagemErro = '';

// Variáveis utilizadas no formulário.
$titulo = '';
$descricao = '';
$dataEvento = '';
$horaEvento = '';
$localId = '';

try {
    // Abre a conexão com o banco.
    $pdo = conectarBanco();

    /*
     * Verifica se o caso existe e pertence
     * ao usuário atualmente logado.
     */
    $consultaCaso = $pdo->prepare(
        'SELECT
            id,
            titulo
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
     * Impede o acesso a um caso inexistente
     * ou pertencente a outro usuário.
     */
    if (!$caso) {
        http_response_code(404);

        echo '<section class="pagina-erro">';
        echo '<div class="container">';
        echo '<h1>Caso não encontrado</h1>';
        echo '<p>Este caso não existe ou não pertence à sua conta.</p>';
        echo '<a href="index.php?pagina=casos" ';
        echo 'class="botao-principal">';
        echo 'Voltar para meus casos';
        echo '</a>';
        echo '</div>';
        echo '</section>';

        return;
    }

    /*
     * Busca os locais já cadastrados neste caso.
     *
     * O local será opcional no formulário.
     */
    $consultaLocais = $pdo->prepare(
        'SELECT
            id,
            nome,
            endereco
         FROM locais
         WHERE caso_id = :caso_id
         ORDER BY nome ASC'
    );

    $consultaLocais->execute([
        ':caso_id' => $casoId
    ]);

    $locais = $consultaLocais->fetchAll();

    /*
     * Verifica se o formulário foi enviado.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recebe os dados do formulário.
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $dataEvento = trim($_POST['data_evento'] ?? '');
        $horaEvento = trim($_POST['hora_evento'] ?? '');
        $localId = trim($_POST['local_id'] ?? '');

        /*
         * Valida os campos obrigatórios.
         */
        if ($titulo === '') {
            $mensagemErro =
                'Informe o título do acontecimento.';
        } elseif (strlen($titulo) > 180) {
            $mensagemErro =
                'O título pode ter no máximo 180 caracteres.';
        } elseif ($dataEvento === '') {
            $mensagemErro =
                'Informe a data do acontecimento.';
        } elseif (!DateTime::createFromFormat(
            'Y-m-d',
            $dataEvento
        )) {
            $mensagemErro =
                'Informe uma data válida.';
        } else {
            /*
             * Se nenhum local foi escolhido,
             * salvaremos o valor como NULL.
             */
            $localIdBanco = $localId !== ''
                ? (int) $localId
                : null;

            /*
             * Se um local foi escolhido, verificamos
             * se ele realmente pertence ao caso atual.
             */
            if ($localIdBanco !== null) {
                $consultaLocal = $pdo->prepare(
                    'SELECT id
                     FROM locais
                     WHERE id = :local_id
                       AND caso_id = :caso_id
                     LIMIT 1'
                );

                $consultaLocal->execute([
                    ':local_id' => $localIdBanco,
                    ':caso_id' => $casoId
                ]);

                $localEncontrado = $consultaLocal->fetch();

                if (!$localEncontrado) {
                    $mensagemErro =
                        'O local escolhido não pertence a este caso.';
                }
            }

            /*
             * Se não houve erro, salva o acontecimento.
             */
            if ($mensagemErro === '') {
                $inserirAcontecimento = $pdo->prepare(
                    'INSERT INTO acontecimentos
                        (
                            caso_id,
                            local_id,
                            titulo,
                            descricao,
                            data_evento,
                            hora_evento
                        )
                     VALUES
                        (
                            :caso_id,
                            :local_id,
                            :titulo,
                            :descricao,
                            :data_evento,
                            :hora_evento
                        )'
                );

                $inserirAcontecimento->execute([
                    ':caso_id' => $casoId,
                    ':local_id' => $localIdBanco,
                    ':titulo' => $titulo,
                    ':descricao' => $descricao !== ''
                        ? $descricao
                        : null,
                    ':data_evento' => $dataEvento,
                    ':hora_evento' => $horaEvento !== ''
                        ? $horaEvento
                        : null
                ]);

                $mensagemSucesso =
                    'Acontecimento cadastrado com sucesso.';

                // Limpa o formulário após o cadastro.
                $titulo = '';
                $descricao = '';
                $dataEvento = '';
                $horaEvento = '';
                $localId = '';
            }
        }
    }

    /*
     * Busca os acontecimentos cadastrados neste caso.
     *
     * A ordenação por data e hora formará a base
     * da futura linha do tempo.
     */
    $consultaAcontecimentos = $pdo->prepare(
        'SELECT
            a.id,
            a.titulo,
            a.descricao,
            a.data_evento,
            a.hora_evento,
            a.criado_em,
            l.nome AS nome_local
         FROM acontecimentos AS a
         LEFT JOIN locais AS l
             ON l.id = a.local_id
         WHERE a.caso_id = :caso_id
         ORDER BY
            a.data_evento ASC,
            a.hora_evento ASC,
            a.id ASC'
    );

    $consultaAcontecimentos->execute([
        ':caso_id' => $casoId
    ]);

    $acontecimentos =
        $consultaAcontecimentos->fetchAll();
} catch (PDOException $erro) {
    error_log(
        'Erro no módulo de acontecimentos: '
        . $erro->getMessage()
    );

    $caso = $caso ?? null;
    $locais = [];
    $acontecimentos = [];

    $mensagemErro =
        'Não foi possível carregar os acontecimentos.';
}
?>

<section class="pagina-acontecimentos">
    <div class="container">

        <div class="acontecimentos-navegacao">
            <a
                href="index.php?pagina=caso&id=<?= (int) $caso['id'] ?>"
                class="link-voltar"
            >
                ← Voltar para o caso
            </a>
        </div>

        <div class="cabecalho-acontecimentos">
            <div>
                <span class="etiqueta">
                    CASO #<?= (int) $caso['id'] ?>
                </span>

                <h1>
                    Acontecimentos do
                    <span>dossiê.</span>
                </h1>

                <p>
                    Caso:
                    <?= htmlspecialchars(
                        $caso['titulo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            </div>
        </div>

        <?php if ($mensagemSucesso !== ''): ?>
            <div class="mensagem mensagem-sucesso">
                <?= htmlspecialchars(
                    $mensagemSucesso,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagemErro !== ''): ?>
            <div class="mensagem mensagem-erro">
                <?= htmlspecialchars(
                    $mensagemErro,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>
        <?php endif; ?>

        <div class="acontecimentos-layout">

            <section class="formulario-acontecimento-card">
                <span class="etiqueta">
                    NOVO REGISTRO
                </span>

                <h2>Cadastrar acontecimento</h2>

                <p class="descricao-card">
                    Registre um fato relacionado ao caso.
                </p>

                <form
                    method="post"
                    action="index.php?pagina=acontecimentos&caso_id=<?= (int) $caso['id'] ?>"
                >
                    <div class="campo">
                        <label for="titulo">
                            Título
                        </label>

                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            value="<?= htmlspecialchars(
                                $titulo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Ex.: Chegada ao local"
                            maxlength="180"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="descricao">
                            Descrição
                        </label>

                        <textarea
                            id="descricao"
                            name="descricao"
                            placeholder="Descreva o acontecimento"
                        ><?= htmlspecialchars(
                            $descricao,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>
                    </div>

                    <div class="campo">
                        <label for="data_evento">
                            Data do acontecimento
                        </label>

                        <input
                            type="date"
                            id="data_evento"
                            name="data_evento"
                            value="<?= htmlspecialchars(
                                $dataEvento,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="hora_evento">
                            Horário
                        </label>

                        <input
                            type="time"
                            id="hora_evento"
                            name="hora_evento"
                            value="<?= htmlspecialchars(
                                $horaEvento,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="local_id">
                            Local relacionado
                        </label>

                        <select
                            id="local_id"
                            name="local_id"
                        >
                            <option value="">
                                Nenhum local selecionado
                            </option>

                            <?php foreach ($locais as $local): ?>
                                <option
                                    value="<?= (int) $local['id'] ?>"
                                    <?= (string) $localId
                                        === (string) $local['id']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $local['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="botao-principal botao-largo"
                    >
                        Salvar acontecimento
                    </button>
                </form>
            </section>

            <section class="lista-acontecimentos-card">
                <div class="lista-acontecimentos-cabecalho">
                    <div>
                        <span class="etiqueta">
                            REGISTROS CRONOLÓGICOS
                        </span>

                        <h2>Linha de acontecimentos</h2>
                    </div>

                    <span class="quantidade-casos">
                        <?= count($acontecimentos) ?>
                    </span>
                </div>

                <?php if (count($acontecimentos) === 0): ?>
                    <div class="acontecimentos-vazio">
                        <h3>Nenhum acontecimento cadastrado</h3>

                        <p>
                            Utilize o formulário para adicionar
                            o primeiro registro cronológico.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="lista-acontecimentos">
                        <?php foreach (
                            $acontecimentos as $acontecimento
                        ): ?>
                            <article class="acontecimento-item">
                                <div class="acontecimento-data">
                                    <strong>
                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $acontecimento[
                                                    'data_evento'
                                                ]
                                            )
                                        ) ?>
                                    </strong>

                                    <?php if (
                                        $acontecimento['hora_evento']
                                        !== null
                                    ): ?>
                                        <span>
                                            <?= substr(
                                                $acontecimento[
                                                    'hora_evento'
                                                ],
                                                0,
                                                5
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="acontecimento-conteudo">
                                    <span class="caso-id">
                                        ACONTECIMENTO
                                    </span>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $acontecimento['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <?php if (
                                        $acontecimento['descricao']
                                        !== null
                                        && $acontecimento['descricao']
                                        !== ''
                                    ): ?>
                                        <p>
                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $acontecimento[
                                                        'descricao'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                            ) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (
                                        $acontecimento['nome_local']
                                        !== null
                                    ): ?>
                                        <small>
                                            Local:
                                            <?= htmlspecialchars(
                                                $acontecimento[
                                                    'nome_local'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>

    </div>
</section>
