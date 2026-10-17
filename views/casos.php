<?php

/*
 * Este arquivo realiza duas funções:
 *
 * 1. Cadastra um novo caso;
 * 2. Lista os casos do advogado logado.
 */

// Inclui o arquivo de conexão com o banco.
require_once __DIR__ . '/../config/conexao.php';

// Recupera o ID do usuário salvo durante o login.
$usuarioId = $_SESSION['usuario_id'];

// Variáveis para mensagens na tela.
$mensagemSucesso = '';
$mensagemErro = '';

// Variáveis utilizadas para manter os dados do formulário.
$titulo = '';
$descricao = '';
$status = 'aberto';

/*
 * Verifica se o formulário foi enviado pelo método POST.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe os dados enviados pelo formulário.
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = $_POST['status'] ?? 'aberto';

    // Lista de status permitidos no sistema.
    $statusPermitidos = [
        'aberto',
        'em_revisao',
        'arquivado'
    ];

    /*
     * Valida os dados antes de realizar o cadastro.
     */
    if ($titulo === '') {
        $mensagemErro = 'Informe o título do caso.';
    } elseif (strlen($titulo) > 180) {
        $mensagemErro = 'O título pode ter no máximo 180 caracteres.';
    } elseif (!in_array($status, $statusPermitidos, true)) {
        $mensagemErro = 'O status informado não é válido.';
    } else {
        try {
            // Abre a conexão com o banco.
            $pdo = conectarBanco();

            /*
             * Insere o caso utilizando uma consulta preparada.
             *
             * O usuario_id vem da sessão.
             * Dessa forma, o caso será vinculado ao advogado logado.
             */
            $inserirCaso = $pdo->prepare(
                'INSERT INTO casos
                    (usuario_id, titulo, descricao, status)
                 VALUES
                    (:usuario_id, :titulo, :descricao, :status)'
            );

            $inserirCaso->execute([
                ':usuario_id' => $usuarioId,
                ':titulo' => $titulo,
                ':descricao' => $descricao !== ''
                    ? $descricao
                    : null,
                ':status' => $status
            ]);

            $mensagemSucesso =
                'Caso cadastrado com sucesso.';

            // Limpa os campos após o cadastro.
            $titulo = '';
            $descricao = '';
            $status = 'aberto';
        } catch (PDOException $erro) {
            error_log(
                'Erro ao cadastrar caso: ' . $erro->getMessage()
            );

            $mensagemErro =
                'Não foi possível cadastrar o caso.';
        }
    }
}

/*
 * Busca somente os casos pertencentes ao usuário logado.
 *
 * O WHERE usuario_id = :usuario_id é muito importante,
 * pois impede que um advogado visualize casos de outra conta.
 */
try {
    $pdo = conectarBanco();

    $consultaCasos = $pdo->prepare(
        'SELECT
            id,
            titulo,
            descricao,
            status,
            criado_em,
            atualizado_em
         FROM casos
         WHERE usuario_id = :usuario_id
         ORDER BY criado_em DESC'
    );

    $consultaCasos->execute([
        ':usuario_id' => $usuarioId
    ]);

    $casos = $consultaCasos->fetchAll();
} catch (PDOException $erro) {
    error_log(
        'Erro ao consultar casos: ' . $erro->getMessage()
    );

    $casos = [];
    $mensagemErro =
        'Não foi possível consultar os casos.';
}

/*
 * Função para transformar o status armazenado no banco
 * em um texto mais compreensível para o usuário.
 */
function apresentarStatus(string $status): string
{
    $nomesStatus = [
        'aberto' => 'Aberto',
        'em_revisao' => 'Em revisão',
        'arquivado' => 'Arquivado'
    ];

    return $nomesStatus[$status] ?? 'Não informado';
}

/*
 * Função para escolher uma classe visual para cada status.
 */
function classeStatus(string $status): string
{
    $classesStatus = [
        'aberto' => 'status-ativo',
        'em_revisao' => 'status-revisao',
        'arquivado' => 'status-arquivado'
    ];

    return $classesStatus[$status] ?? 'status-arquivado';
}
?>

<section class="pagina-casos">
    <div class="container">

        <div class="casos-cabecalho">
            <div>
                <span class="etiqueta">DOSSIÊS DO ADVOGADO</span>

                <h1>
                    Meus
                    <span>casos.</span>
                </h1>

                <p>
                    Cadastre e organize os casos vinculados
                    à sua conta profissional simulada.
                </p>
            </div>

            <a
                href="index.php?pagina=painel"
                class="botao-secundario"
            >
                Voltar ao painel
            </a>
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

        <div class="casos-layout">

            <div class="novo-caso-card">
                <span class="etiqueta">NOVO REGISTRO</span>

                <h2>Cadastrar caso</h2>

                <p class="descricao-card">
                    Crie um novo caso para iniciar a organização
                    das informações.
                </p>

                <form
                    method="post"
                    action="index.php?pagina=casos"
                >
                    <div class="campo">
                        <label for="titulo">Título do caso</label>

                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            value="<?= htmlspecialchars(
                                $titulo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Ex.: Caso Simulado Alpha"
                            maxlength="180"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="descricao">Descrição</label>

                        <textarea
                            id="descricao"
                            name="descricao"
                            placeholder="Descreva resumidamente o caso"
                        ><?= htmlspecialchars(
                            $descricao,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>
                    </div>

                    <div class="campo">
                        <label for="status">Status inicial</label>

                        <select id="status" name="status">
                            <option
                                value="aberto"
                                <?= $status === 'aberto'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Aberto
                            </option>

                            <option
                                value="em_revisao"
                                <?= $status === 'em_revisao'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Em revisão
                            </option>

                            <option
                                value="arquivado"
                                <?= $status === 'arquivado'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Arquivado
                            </option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="botao-principal botao-largo"
                    >
                        Salvar caso
                    </button>
                </form>
            </div>

            <div class="lista-casos">
                <div class="lista-casos-cabecalho">
                    <div>
                        <span class="etiqueta">REGISTROS SALVOS</span>
                        <h2>Casos cadastrados</h2>
                    </div>

                    <span class="quantidade-casos">
                        <?= count($casos) ?>
                    </span>
                </div>

                <?php if (count($casos) === 0): ?>
                    <div class="casos-vazio">
                        <h3>Nenhum caso cadastrado</h3>

                        <p>
                            Utilize o formulário ao lado para criar
                            o primeiro caso do Projeto VERITAS.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="lista">
                        <?php foreach ($casos as $caso): ?>
                            <article class="caso-item">
                                <div class="caso-item-topo">
                                    <span class="caso-id">
                                        CASO #<?= (int) $caso['id'] ?>
                                    </span>

                                    <span class="<?= htmlspecialchars(
                                        classeStatus($caso['status']),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">
                                        <?= htmlspecialchars(
                                            apresentarStatus(
                                                $caso['status']
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </div>

                                <h3>
                                    <a
                                        href="index.php?pagina=caso&id=<?= (int) $caso['id'] ?>"
                                        class="link-caso"
                                    >
                                        <?= htmlspecialchars(
                                            $caso['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </a>
                                </h3>


                                <?php if (
                                    $caso['descricao'] !== null
                                    && $caso['descricao'] !== ''
                                ): ?>
                                    <p>
                                        <?= nl2br(
                                            htmlspecialchars(
                                                $caso['descricao'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="texto-sem-descricao">
                                        Este caso ainda não possui
                                        uma descrição.
                                    </p>
                                <?php endif; ?>

                                <small>
                                    Cadastrado em:
                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($caso['criado_em'])
                                    ) ?>
                                </small>
                                <a
                                    href="index.php?pagina=caso&id=<?= (int) $caso['id'] ?>"
                                    class="botao-abrir-caso"
                                >
                                    Abrir detalhes
                                </a>

                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</section>
