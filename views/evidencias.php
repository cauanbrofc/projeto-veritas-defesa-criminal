<?php

/*
 * Módulo de evidências do Projeto VERITAS.
 *
 * Permite cadastrar evidências e, opcionalmente,
 * anexar arquivos PDF, JPG ou PNG.
 */

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../config/configuracoes_locais.php';

$usuarioId = $_SESSION['usuario_id'] ?? null;

$casoId = filter_input(
    INPUT_GET,
    'caso_id',
    FILTER_VALIDATE_INT
);

if (!$usuarioId || !$casoId) {
    header('Location: index.php?pagina=casos');
    exit;
}

$mensagemSucesso = '';
$mensagemErro = '';

$titulo = '';
$descricao = '';
$categoriaId = '';
$localId = '';
$dataRegistro = '';
$horaRegistro = '';

$categorias = [];
$locais = [];
$evidencias = [];

try {
    $pdo = conectarBanco();

    /* Confirma que o caso pertence ao usuário logado. */
    $consultaCaso = $pdo->prepare(
        'SELECT id, titulo
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

    if (!$caso) {
        http_response_code(404);
        echo '<section class="pagina-erro">';
        echo '<div class="container">';
        echo '<h1>Caso não encontrado</h1>';
        echo '<p>Este caso não existe ou não pertence à sua conta.</p>';
        echo '<a href="index.php?pagina=casos" class="botao-principal">';
        echo 'Voltar para meus casos';
        echo '</a>';
        echo '</div>';
        echo '</section>';
        return;
    }

    /* Busca as categorias disponíveis. */
    $consultaCategorias = $pdo->query(
        'SELECT id, nome
         FROM categorias_evidencia
         ORDER BY nome ASC'
    );

    $categorias = $consultaCategorias->fetchAll();

    /* Busca somente os locais deste caso. */
    $consultaLocais = $pdo->prepare(
        'SELECT id, nome
         FROM locais
         WHERE caso_id = :caso_id
         ORDER BY nome ASC'
    );

    $consultaLocais->execute([
        ':caso_id' => $casoId
    ]);

    $locais = $consultaLocais->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoriaId = trim($_POST['categoria_id'] ?? '');
        $localId = trim($_POST['local_id'] ?? '');
        $dataRegistro = trim($_POST['data_registro'] ?? '');
        $horaRegistro = trim($_POST['hora_registro'] ?? '');
        $arquivoUpload = $_FILES['arquivo'] ?? null;

        if ($titulo === '') {
            $mensagemErro = 'Informe o título da evidência.';
        } elseif (strlen($titulo) > 180) {
            $mensagemErro =
                'O título pode ter no máximo 180 caracteres.';
        }

        /* Valida a data, se ela foi informada. */
        if (
            $mensagemErro === ''
            && $dataRegistro !== ''
        ) {
            $dataValida = DateTime::createFromFormat(
                'Y-m-d',
                $dataRegistro
            );

            if (!$dataValida || $dataValida->format('Y-m-d') !== $dataRegistro) {
                $mensagemErro = 'Informe uma data válida.';
            }
        }

        /* Confirma se a categoria existe. */
        if (
            $mensagemErro === ''
            && $categoriaId !== ''
        ) {
            $consultaCategoria = $pdo->prepare(
                'SELECT id
                 FROM categorias_evidencia
                 WHERE id = :categoria_id
                 LIMIT 1'
            );

            $consultaCategoria->execute([
                ':categoria_id' => (int) $categoriaId
            ]);

            if (!$consultaCategoria->fetch()) {
                $mensagemErro =
                    'A categoria escolhida não existe.';
            }
        }

        /* Confirma se o local pertence ao caso atual. */
        if (
            $mensagemErro === ''
            && $localId !== ''
        ) {
            $consultaLocal = $pdo->prepare(
                'SELECT id
                 FROM locais
                 WHERE id = :local_id
                   AND caso_id = :caso_id
                 LIMIT 1'
            );

            $consultaLocal->execute([
                ':local_id' => (int) $localId,
                ':caso_id' => $casoId
            ]);

            if (!$consultaLocal->fetch()) {
                $mensagemErro =
                    'O local escolhido não pertence a este caso.';
            }
        }

        $arquivoMovido = false;
        $caminhoCompleto = '';
        $arquivoNomeOriginal = '';
        $arquivoNomeArmazenado = '';
        $arquivoCaminhoRelativo = '';
        $arquivoTipoMime = '';
        $arquivoHash = '';

        /* O anexo é opcional. Se existir, valida e armazena. */
        if (
            $mensagemErro === ''
            && $arquivoUpload !== null
            && $arquivoUpload['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($arquivoUpload['error'] !== UPLOAD_ERR_OK) {
                $mensagemErro = 'O arquivo não pôde ser enviado.';
            } elseif ($arquivoUpload['size'] > 5 * 1024 * 1024) {
                $mensagemErro =
                    'O arquivo deve ter no máximo 5 MB.';
            } elseif (!is_uploaded_file($arquivoUpload['tmp_name'])) {
                $mensagemErro = 'Upload de arquivo inválido.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $tipoMime = $finfo->file($arquivoUpload['tmp_name']);

                $tiposPermitidos = [
                    'application/pdf' => 'pdf',
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png'
                ];

                if (!array_key_exists($tipoMime, $tiposPermitidos)) {
                    $mensagemErro =
                        'Formato de arquivo não permitido. Use PDF, JPG ou PNG.';
                } else {
                    $arquivoNomeOriginal = basename($arquivoUpload['name']);
                    $extensao = $tiposPermitidos[$tipoMime];
                    $arquivoNomeArmazenado =
                        bin2hex(random_bytes(16)) . '.' . $extensao;
                    $arquivoTipoMime = $tipoMime;
                    $arquivoHash = hash_file(
                        'sha256',
                        $arquivoUpload['tmp_name']
                    );

                    $pastaArmazenamento =
                        PASTA_ARMAZENAMENTO_EVIDENCIAS;

                    if (!is_dir($pastaArmazenamento)) {
                        if (!mkdir($pastaArmazenamento, 0750, true)) {
                            $mensagemErro =
                                'Não foi possível criar a pasta de armazenamento.';
                        }
                    }

                    if ($mensagemErro === '') {
                        $caminhoCompleto =
                            rtrim($pastaArmazenamento, '/\\')
                            . DIRECTORY_SEPARATOR
                            . $arquivoNomeArmazenado;

                        $arquivoCaminhoRelativo =
                            'evidencias/' . $arquivoNomeArmazenado;

                        if (!move_uploaded_file(
                            $arquivoUpload['tmp_name'],
                            $caminhoCompleto
                        )) {
                            $mensagemErro =
                                'Não foi possível armazenar o arquivo.';
                        } else {
                            $arquivoMovido = true;
                        }
                    }
                }
            }
        }

        if ($mensagemErro === '') {
            try {
                $pdo->beginTransaction();

                $inserirEvidencia = $pdo->prepare(
                    'INSERT INTO evidencias
                        (
                            caso_id,
                            categoria_id,
                            local_id,
                            titulo,
                            descricao,
                            data_registro,
                            hora_registro
                        )
                     VALUES
                        (
                            :caso_id,
                            :categoria_id,
                            :local_id,
                            :titulo,
                            :descricao,
                            :data_registro,
                            :hora_registro
                        )'
                );

                $inserirEvidencia->execute([
                    ':caso_id' => $casoId,
                    ':categoria_id' => $categoriaId !== ''
                        ? (int) $categoriaId
                        : null,
                    ':local_id' => $localId !== ''
                        ? (int) $localId
                        : null,
                    ':titulo' => $titulo,
                    ':descricao' => $descricao !== ''
                        ? $descricao
                        : null,
                    ':data_registro' => $dataRegistro !== ''
                        ? $dataRegistro
                        : null,
                    ':hora_registro' => $horaRegistro !== ''
                        ? $horaRegistro
                        : null
                ]);

                $evidenciaId = (int) $pdo->lastInsertId();

                if ($arquivoMovido) {
                    $inserirArquivo = $pdo->prepare(
                        'INSERT INTO arquivos_evidencia
                            (
                                evidencia_id,
                                nome_original,
                                nome_armazenado,
                                caminho_arquivo,
                                tipo_mime,
                                tamanho_bytes,
                                hash_sha256
                            )
                         VALUES
                            (
                                :evidencia_id,
                                :nome_original,
                                :nome_armazenado,
                                :caminho_arquivo,
                                :tipo_mime,
                                :tamanho_bytes,
                                :hash_sha256
                            )'
                    );

                    $inserirArquivo->execute([
                        ':evidencia_id' => $evidenciaId,
                        ':nome_original' => $arquivoNomeOriginal,
                        ':nome_armazenado' => $arquivoNomeArmazenado,
                        ':caminho_arquivo' => $arquivoCaminhoRelativo,
                        ':tipo_mime' => $arquivoTipoMime,
                        ':tamanho_bytes' => $arquivoUpload['size'],
                        ':hash_sha256' => $arquivoHash
                    ]);
                }

                $pdo->commit();

                $mensagemSucesso =
                    'Evidência cadastrada com sucesso.';

                if ($arquivoMovido) {
                    $mensagemSucesso .=
                        ' Arquivo armazenado com segurança.';
                }

                $titulo = '';
                $descricao = '';
                $categoriaId = '';
                $localId = '';
                $dataRegistro = '';
                $horaRegistro = '';
            } catch (PDOException $erro) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (
                    $arquivoMovido
                    && $caminhoCompleto !== ''
                    && file_exists($caminhoCompleto)
                ) {
                    unlink($caminhoCompleto);
                }

                error_log(
                    'Erro ao salvar evidência: '
                    . $erro->getMessage()
                );

                $mensagemErro =
                    'Não foi possível salvar a evidência.';
            }
        } elseif (
            $arquivoMovido
            && $caminhoCompleto !== ''
            && file_exists($caminhoCompleto)
        ) {
            unlink($caminhoCompleto);
        }
    }

    /* Carrega as evidências do caso para a listagem. */
    $consultaEvidencias = $pdo->prepare(
        'SELECT
            e.id,
            e.titulo,
            e.descricao,
            e.data_registro,
            e.hora_registro,
            ce.nome AS nome_categoria,
            l.nome AS nome_local
         FROM evidencias AS e
         LEFT JOIN categorias_evidencia AS ce
            ON ce.id = e.categoria_id
         LEFT JOIN locais AS l
            ON l.id = e.local_id
         WHERE e.caso_id = :caso_id
         ORDER BY
            e.data_registro DESC,
            e.hora_registro DESC,
            e.id DESC'
    );

    $consultaEvidencias->execute([
        ':caso_id' => $casoId
    ]);

    $evidencias = $consultaEvidencias->fetchAll();
} catch (PDOException $erro) {
    error_log(
        'Erro no módulo de evidências: '
        . $erro->getMessage()
    );

    $mensagemErro =
        'Não foi possível carregar as informações.';
}
?>

<section class="pagina-evidencias">
    <div class="container">
        <div class="evidencias-navegacao">
            <a
                href="index.php?pagina=caso&id=<?= (int) $caso['id'] ?>"
                class="link-voltar"
            >
                ← Voltar para o caso
            </a>
        </div>

        <div class="cabecalho-evidencias">
            <div>
                <span class="etiqueta">
                    CASO #<?= (int) $caso['id'] ?>
                </span>

                <h1>Evidências do <span>dossiê.</span></h1>

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

        <div class="evidencias-layout">
            <section class="formulario-evidencia-card">
                <span class="etiqueta">NOVO REGISTRO</span>
                <h2>Cadastrar evidência</h2>
                <p class="descricao-card">
                    Registre uma informação relacionada ao caso.
                </p>

                <form
                    method="post"
                    enctype="multipart/form-data"
                    action="index.php?pagina=evidencias&amp;caso_id=<?= (int) $caso['id'] ?>"
                >
                    <div class="campo">
                        <label for="titulo">Título</label>
                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            value="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex.: Termo de entrevista"
                            maxlength="180"
                            required
                        >
                    </div>

                    <div class="campo">
                        <label for="descricao">Descrição</label>
                        <textarea
                            id="descricao"
                            name="descricao"
                            placeholder="Descreva a evidência"
                        ><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="campo">
                        <label for="categoria_id">Categoria</label>
                        <select id="categoria_id" name="categoria_id">
                            <option value="">Nenhuma categoria selecionada</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option
                                    value="<?= (int) $categoria['id'] ?>"
                                    <?= (string) $categoriaId === (string) $categoria['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="local_id">Local relacionado</label>
                        <select id="local_id" name="local_id">
                            <option value="">Nenhum local selecionado</option>
                            <?php foreach ($locais as $local): ?>
                                <option
                                    value="<?= (int) $local['id'] ?>"
                                    <?= (string) $localId === (string) $local['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($local['nome'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="data_registro">Data do registro</label>
                        <input
                            type="date"
                            id="data_registro"
                            name="data_registro"
                            value="<?= htmlspecialchars($dataRegistro, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="hora_registro">Horário do registro</label>
                        <input
                            type="time"
                            id="hora_registro"
                            name="hora_registro"
                            value="<?= htmlspecialchars($horaRegistro, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    </div>

                    <div class="campo">
                        <label for="arquivo">Arquivo da evidência</label>
                        <input
                            type="file"
                            id="arquivo"
                            name="arquivo"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >
                        <small class="texto-ajuda">
                            Formatos permitidos: PDF, JPG e PNG. Tamanho máximo: 5 MB.
                        </small>
                    </div>

                    <button
                        type="submit"
                        class="botao-principal botao-largo"
                    >
                        Salvar evidência
                    </button>
                </form>
            </section>

            <section class="lista-evidencias-card">
                <div class="lista-evidencias-cabecalho">
                    <div>
                        <span class="etiqueta">REGISTROS DO CASO</span>
                        <h2>Evidências cadastradas</h2>
                    </div>
                    <span class="quantidade-casos">
                        <?= count($evidencias) ?>
                    </span>
                </div>

                <?php if (count($evidencias) === 0): ?>
                    <div class="evidencias-vazio">
                        <h3>Nenhuma evidência cadastrada</h3>
                        <p>
                            Utilize o formulário para adicionar o primeiro registro.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="lista-evidencias">
                        <?php foreach ($evidencias as $evidencia): ?>
                            <article class="evidencia-item">
                                <div class="evidencia-item-topo">
                                    <span class="caso-id">
                                        EVIDÊNCIA #<?= (int) $evidencia['id'] ?>
                                    </span>

                                    <?php if ($evidencia['data_registro'] !== null): ?>
                                        <span class="evidencia-data">
                                            <?= date('d/m/Y', strtotime($evidencia['data_registro'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3>
                                    <?= htmlspecialchars($evidencia['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                </h3>

                                <?php if (
                                    $evidencia['descricao'] !== null
                                    && $evidencia['descricao'] !== ''
                                ): ?>
                                    <p>
                                        <?= nl2br(htmlspecialchars(
                                            $evidencia['descricao'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="evidencia-relacoes">
                                    <?php if ($evidencia['nome_categoria'] !== null): ?>
                                        <span>
                                            Categoria:
                                            <?= htmlspecialchars(
                                                $evidencia['nome_categoria'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($evidencia['nome_local'] !== null): ?>
                                        <span>
                                            Local:
                                            <?= htmlspecialchars(
                                                $evidencia['nome_local'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($evidencia['hora_registro'] !== null): ?>
                                        <span>
                                            Horário:
                                            <?= substr($evidencia['hora_registro'], 0, 5) ?>
                                        </span>
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
