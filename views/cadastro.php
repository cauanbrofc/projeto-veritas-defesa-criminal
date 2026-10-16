<?php

require_once __DIR__ . '/../config/conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

$nome = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
        $mensagemErro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagemErro = 'Informe um endereço de e-mail válido.';
    } elseif (strlen($senha) < 8) {
        $mensagemErro = 'A senha deve possuir pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $mensagemErro = 'A confirmação da senha não corresponde à senha informada.';
    } else {
        try {
            $pdo = conectarBanco();

            $consultaEmail = $pdo->prepare(
                'SELECT id FROM usuarios WHERE email = :email LIMIT 1'
            );

            $consultaEmail->execute([
                ':email' => $email
            ]);

            $usuarioExistente = $consultaEmail->fetch();

            if ($usuarioExistente) {
                $mensagemErro = 'Este e-mail já está cadastrado.';
            } else {
                $senhaHash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                $inserirUsuario = $pdo->prepare(
                    'INSERT INTO usuarios
                        (nome, email, senha_hash)
                     VALUES
                        (:nome, :email, :senha_hash)'
                );

                $inserirUsuario->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha_hash' => $senhaHash
                ]);

                $mensagemSucesso =
                    'Conta criada com sucesso. Agora você já pode entrar no sistema.';

                $nome = '';
                $email = '';
            }
        } catch (PDOException $erro) {
            error_log(
                'Erro ao cadastrar usuário: ' . $erro->getMessage()
            );

            $mensagemErro =
                'Não foi possível concluir o cadastro. Tente novamente.';
        }
    }
}
?>

<section class="pagina-formulario">
    <div class="container formulario-container">

        <div class="formulario-introducao">
            <span class="etiqueta">PRIMEIRO ACESSO</span>

            <h1>
                Crie sua conta
                <span>profissional.</span>
            </h1>

            <p>
                Cadastre uma conta simulada para acessar
                o protótipo do Projeto VERITAS.
            </p>
        </div>

        <form
            class="formulario"
            method="post"
            action="index.php?pagina=cadastro"
        >
            <?php if ($mensagemSucesso !== ''): ?>
                <div class="mensagem mensagem-sucesso">
                    <?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8') ?>

                    <a href="index.php?pagina=login">
                        Ir para o login
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($mensagemErro !== ''): ?>
                <div class="mensagem mensagem-erro">
                    <?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="campo">
                <label for="nome">Nome completo</label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Digite seu nome"
                    maxlength="150"
                    required
                >
            </div>

            <div class="campo">
                <label for="email">E-mail</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="advogado@exemplo.com"
                    maxlength="254"
                    required
                >
            </div>

            <div class="campo">
                <label for="senha">Senha</label>

                <input
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="Mínimo de 8 caracteres"
                    minlength="8"
                    required
                >
            </div>

            <div class="campo">
                <label for="confirmar_senha">Confirmar senha</label>

                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    placeholder="Repita sua senha"
                    minlength="8"
                    required
                >
            </div>

            <button type="submit" class="botao-principal botao-largo">
                Criar conta
            </button>

            <p class="texto-ajuda">
                Já possui uma conta?
                <a href="index.php?pagina=login">
                    Entrar no sistema
                </a>
            </p>
        </form>

    </div>
</section>
