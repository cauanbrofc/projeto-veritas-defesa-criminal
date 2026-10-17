<?php

/*
 * Módulo de pessoas participantes.
 *
 * Este arquivo permite:
 *
 * 1. Verificar se o caso pertence ao usuário logado;
 * 2. Cadastrar uma pessoa;
 * 3. Relacionar a pessoa ao caso;
 * 4. Listar as pessoas relacionadas ao caso.
 */

require_once __DIR__ . '/../config/conexao.php';

// Recupera o usuário atualmente logado.
$usuarioId = $_SESSION['usuario_id'];

// Recupera o ID do caso enviado pela URL.
$casoId = filter_input(
    INPUT_GET,
    'caso_id',
    FILTER_VALIDATE_INT
);

// Se o ID do caso for inválido, volta para a lista de casos.
if (!$casoId) {
    header('Location: index.php?pagina=casos');
    exit;
}

// Variáveis para mensagens.
$mensagemSucesso = '';
$mensagemErro = '';

// Variáveis usadas no formulário.
$nome = '';
$papel = '';
$observacoes = '';

try {
    $pdo = conectarBanco();

    /*
     * Primeiro verificamos se o caso existe
     * e pertence ao usuário logado.
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

    $caso = $consultaCaso->fetch();

    // Caso não pertença ao usuário, o acesso será bloqueado.
    if (!$caso) {
        http_response_code(404 );

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
     * Verifica se o formulário foi enviado.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recebe e limpa os dados enviados.
        $nome = trim($_POST['nome'] ?? '');
        $papel = trim($_POST['papel'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Verifica se os campos obrigatórios foram preenchidos.
        if ($nome === '') {
            $mensagemErro =
                'Informe o nome da pessoa.';
        } elseif ($papel === '') {
            $mensagemErro =
                'Informe o papel da pessoa no caso.';
        } elseif (strlen($nome) > 150) {
            $mensagemErro =
                'O nome pode ter no máximo 150 caracteres.';
        } else {
            /*
             * A transação garante que as duas operações
             * aconteçam juntas:
             *
             * 1. Inserir a pessoa;
             * 2. Relacionar a pessoa ao caso.
             */
            $pdo->beginTransaction();

            try {
                // Insere a pessoa na tabela pessoas.
                $inserirPessoa = $pdo->prepare(
                    'INSERT INTO pessoas
                        (nome, tipo, observacoes)
                     VALUES
                        (:nome, :tipo, :observacoes)'
                );

                $inserirPessoa->execute([
                    ':nome' => $nome,
                    ':tipo' => $papel,
                    ':observacoes' => $observacoes !== ''
                        ? $observacoes
                        : null
                ]);

                /*
                 * Recupera o ID gerado para a pessoa recém-criada.
                 */
                $pessoaId = (int) $pdo->lastInsertId();

                // Relaciona a pessoa ao caso.
                $relacionarPessoa = $pdo->prepare(
                    'INSERT INTO caso_pessoas
                        (caso_id, pessoa_id, papel, observacoes)
                     VALUES
                        (:caso_id, :pessoa_id, :papel, :observacoes)'
                );

                $relacionarPessoa->execute([
                    ':caso_id' => $casoId,
                    ':pessoa_id' => $pessoaId,
                    ':papel' => $papel,
                    ':observacoes' => $observacoes !== ''
                        ? $observacoes
                        : null
                ]);

                // Confirma as duas operações.
                $pdo->commit();

                $mensagemSucesso =
                    'Pessoa cadastrada com sucesso.';

                // Limpa o formulário.
                $nome = '';
                $papel = '';
                $observacoes = '';
            } catch (PDOException $erro) {
                /*
                 * Se alguma operação falhar, desfaz as alterações.
                 */
                $pdo->rollBack();

                error_log(
                    'Erro ao cadastrar pessoa: '
                    . $erro->getMessage()
                );

                $mensagemErro =
                    'Não foi possível cadastrar a pessoa.';
            }
        }
    }

    /*
     * Busca as pessoas relacionadas ao caso.
     */
    $consultaPessoas = $pdo->prepare(
        'SELECT
            p.id,
            p.nome,
            p.tipo,
            p.observacoes,
            cp.papel,
            cp.criado_em
         FROM pessoas AS p
         INNER JOIN caso_pessoas AS cp
             ON cp.pessoa_id = p.id
         WHERE cp.caso_id = :caso_id
         ORDER BY p.nome ASC'
    );

    $consultaPessoas->execute([
        ':caso_id' => $casoId
    ]);

    $pessoas = $consultaPessoas->fetchAll();
} catch (PDOException $erro) {
    error_log(
        'Erro no módulo de pessoas: '
        . $erro->getMessage()
    );

    $caso = $caso ?? null;
    $pessoas = [];
    $mensagemErro =
        'Não foi possível carregar as informações.';
}
?>

<section class="pagina-pessoas">
    <div class="container">

        <div class="pessoas-navegacao">
            <a
                href="index.php?pagina=caso&id=<?= (int) $caso['id'] ?>"
                class="link-voltar"
            >
                ← Voltar para o caso
            </a>
        </div>

        <div class="cabecalho-pessoas">
            <div>
                <span class="etiqueta">
                    CASO #<?= (int) $caso['id'] ?>
                </span>

                <h1>
                    Pessoas do
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

        <div class="pessoas-layout">

            <section class="formulario-pessoa-card">
                <span class="etiqueta">NOVO PARTICIPANTE</span>

                <h2>Cadastrar pessoa</h2>

                <p class="descricao-card">
                    Registre uma pessoa relacionada a este caso.
                </p>

                <form
                    method="post"
                    action="index.php?pagina=pessoas&caso_id=<?= (int) $caso['id'] ?>"
                >
                    <div class="campo">
                        <label for="nome">
                            Nome completo
                        </label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            value="<?= htmlspecialchars(
                                $nome,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Ex.: Carlos Almeida"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="papel">
                            Papel no caso
                        </label>

                        <select
                            id="papel"
                            name="papel"
                            required
                        >
                            <option value="">
                                Selecione uma opção
                            </option>

                            <option
                                value="Acusado"
                                <?= $papel === 'Acusado'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Acusado
                            </option>

                            <option
                                value="Vítima"
                                <?= $papel === 'Vítima'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Vítima
                            </option>

                            <option
                                value="Testemunha"
                                <?= $papel === 'Testemunha'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Testemunha
                            </option>

                            <option
                                value="Perito"
                                <?= $papel === 'Perito'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Perito
                            </option>

                            <option
                                value="Investigador"
                                <?= $papel === 'Investigador'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Investigador
                            </option>

                            <option
                                value="Outra pessoa"
                                <?= $papel === 'Outra pessoa'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Outra pessoa
                            </option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="observacoes">
                            Observações
                        </label>

                        <textarea
                            id="observacoes"
                            name="observacoes"
                            placeholder="Observações opcionais"
                        ><?= htmlspecialchars(
                            $observacoes,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>
                    </div>

                    <button
                        type="submit"
                        class="botao-principal botao-largo"
                    >
                        Salvar pessoa
                    </button>
                </form>
            </section>

            <section class="lista-pessoas-card">
                <div class="lista-pessoas-cabecalho">
                    <div>
                        <span class="etiqueta">
                            PARTICIPANTES REGISTRADOS
                        </span>

                        <h2>Pessoas do caso</h2>
                    </div>

                    <span class="quantidade-casos">
                        <?= count($pessoas) ?>
                    </span>
                </div>

                <?php if (count($pessoas) === 0): ?>
                    <div class="pessoas-vazio">
                        <h3>Nenhuma pessoa cadastrada</h3>

                        <p>
                            Utilize o formulário para adicionar
                            o primeiro participante.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="lista-pessoas">
                        <?php foreach ($pessoas as $pessoa): ?>
                            <article class="pessoa-item">
                                <div>
                                    <span class="caso-id">
                                        PARTICIPANTE
                                    </span>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $pessoa['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <span class="selo-papel">
                                        <?= htmlspecialchars(
                                            $pessoa['papel'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </div>

                                <?php if (
                                    $pessoa['observacoes'] !== null
                                    && $pessoa['observacoes'] !== ''
                                ): ?>
                                    <p>
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $pessoa['observacoes'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ) ?>
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>

    </div>
</section>
