<?php

/*
 * Módulo de locais relacionados ao caso.
 *
 * Este arquivo permite:
 *
 * 1. Verificar se o caso pertence ao usuário logado;
 * 2. Cadastrar um local;
 * 3. Relacionar o local ao caso;
 * 4. Listar os locais relacionados ao caso.
 */

// Inclui o arquivo de conexão com o banco.
require_once __DIR__ . '/../config/conexao.php';

// Recupera o ID do usuário que está logado.
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

// Variáveis utilizadas para exibir mensagens.
$mensagemSucesso = '';
$mensagemErro = '';

// Variáveis utilizadas no formulário.
$nome = '';
$endereco = '';
$observacoes = '';

try {
    /*
     * Abre a conexão com o banco de dados.
     */
    $pdo = conectarBanco();

    /*
     * Verifica se o caso existe e pertence ao usuário logado.
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
     * Se o caso não existir ou não pertencer ao usuário,
     * o acesso será bloqueado.
     */
    if (!$caso) {
        http_response_code(404);
        ?>

        <section class="pagina-erro">
            <div class="container">
                <h1>Caso não encontrado</h1>

                <p>
                    Este caso não existe ou não pertence à sua conta.
                </p>

                <a
                    href="index.php?pagina=casos"
                    class="botao-principal"
                >
                    Voltar para meus casos
                </a>
            </div>
        </section>

        <?php
        return;
    }

    /*
     * Verifica se o formulário foi enviado.
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        /*
         * Recebe os dados enviados pelo formulário.
         */
        $nome = trim($_POST['nome'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        /*
         * Faz as validações básicas.
         */
        if ($nome === '') {
            $mensagemErro =
                'Informe o nome do local.';
        } elseif (strlen($nome) > 150) {
            $mensagemErro =
                'O nome pode ter no máximo 150 caracteres.';
        } elseif (strlen($endereco) > 255) {
            $mensagemErro =
                'O endereço pode ter no máximo 255 caracteres.';
        } else {
            /*
             * A transação agrupa duas operações:
             *
             * 1. Cadastrar o local;
             * 2. Relacionar o local ao caso.
             *
             * Se uma delas falhar, nenhuma alteração será mantida.
             */
            $pdo->beginTransaction();

            try {
                /*
 * Insere o local diretamente relacionado ao caso.
 *
 * A coluna caso_id é obrigatória na tabela locais.
 * Por isso, enviamos o ID do caso junto com os demais dados.
 */
$inserirLocal = $pdo->prepare(
    'INSERT INTO locais
        (caso_id, nome, endereco, observacoes)
     VALUES
        (:caso_id, :nome, :endereco, :observacoes)'
);

$inserirLocal->execute([
    ':caso_id' => $casoId,
    ':nome' => $nome,
    ':endereco' => $endereco !== ''
        ? $endereco
        : null,
    ':observacoes' => $observacoes !== ''
        ? $observacoes
        : null
]);


                /*
                 * Confirma as duas operações.
                 */
                $pdo->commit();

                $mensagemSucesso =
                    'Local cadastrado com sucesso.';

                /*
                 * Limpa os campos do formulário após o cadastro.
                 */
                $nome = '';
                $endereco = '';
                $observacoes = '';
            } catch (PDOException $erro) {
                /*
                 * Se a transação estiver ativa, desfaz as operações.
                 */
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                /*
                 * Registra o erro no log do PHP.
                 */
                error_log(
                    'Erro ao cadastrar local: '
                    . $erro->getMessage()
                );

                /*
                 * Mensagem detalhada temporária.
                 *
                 * Ela será útil para identificarmos
                 * algum problema restante no banco.
                 */
                $mensagemErro =
                    'Erro detalhado ao cadastrar: '
                    . $erro->getMessage();
            }
        }
    }

    
    /*
  Busca diretamente os locais vinculados ao caso.
 */
$consultaLocais = $pdo->prepare(
    'SELECT
        id,
        nome,
        endereco,
        observacoes,
        criado_em
     FROM locais
     WHERE caso_id = :caso_id
     ORDER BY nome ASC'
);


    $consultaLocais->execute([
        ':caso_id' => $casoId
    ]);

    // Armazena todos os locais encontrados.
    $locais = $consultaLocais->fetchAll();
} catch (PDOException $erro) {
    /*
     * Este bloco trata erros ao carregar:
     *
     * - a conexão;
     * - o caso;
     * - a lista de locais.
     */
    error_log(
        'Erro ao carregar locais: '
        . $erro->getMessage()
    );

    $caso = $caso ?? null;
    $locais = [];

    $mensagemErro =
        'Não foi possível carregar as informações.';
}
?>

<section class="pagina-locais">
    <div class="container">

        <div class="locais-navegacao">
            <a
                href="index.php?pagina=caso&id=<?= (int) $caso['id'] ?>"
                class="link-voltar"
            >
                ← Voltar para o caso
            </a>
        </div>

        <div class="cabecalho-locais">
            <div>
                <span class="etiqueta">
                    CASO #<?= (int) $caso['id'] ?>
                </span>

                <h1>
                    Locais do
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

        <div class="locais-layout">

            <section class="formulario-local-card">
                <span class="etiqueta">
                    NOVO REGISTRO
                </span>

                <h2>Cadastrar local</h2>

                <p class="descricao-card">
                    Registre um local relacionado ao caso.
                </p>

                <form
                    method="post"
                    action="index.php?pagina=locais&caso_id=<?= (int) $caso['id'] ?>"
                >
                    <div class="campo">
                        <label for="nome">
                            Nome do local
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
                            placeholder="Ex.: Residência da vítima"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="endereco">
                            Endereço
                        </label>

                        <input
                            type="text"
                            id="endereco"
                            name="endereco"
                            value="<?= htmlspecialchars(
                                $endereco,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Ex.: Rua das Flores, nº 100"
                            maxlength="255"
                        >
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
                        Salvar local
                    </button>
                </form>
            </section>

            <section class="lista-locais-card">
                <div class="lista-locais-cabecalho">
                    <div>
                        <span class="etiqueta">
                            LOCAIS REGISTRADOS
                        </span>

                        <h2>Locais do caso</h2>
                    </div>

                    <span class="quantidade-casos">
                        <?= count($locais) ?>
                    </span>
                </div>

                <?php if (count($locais) === 0): ?>
                    <div class="locais-vazio">
                        <h3>Nenhum local cadastrado</h3>

                        <p>
                            Utilize o formulário para adicionar
                            o primeiro local.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="lista-locais">
                        <?php foreach ($locais as $local): ?>
                            <article class="local-item">
                                <div>
                                    <span class="caso-id">
                                        LOCAL REGISTRADO
                                    </span>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $local['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>
                                </div>

                                <?php if (
                                    $local['endereco'] !== null
                                    && $local['endereco'] !== ''
                                ): ?>
                                    <p class="local-endereco">
                                        <strong>Endereço:</strong>

                                        <?= htmlspecialchars(
                                            $local['endereco'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (
                                    $local['observacoes'] !== null
                                    && $local['observacoes'] !== ''
                                ): ?>
                                    <p class="local-observacoes">
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $local['observacoes'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <small>
                                    Cadastrado em:

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $local['criado_em']
                                        )
                                    ) ?>
                                </small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>

    </div>
</section>
