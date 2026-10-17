<?php

/*
 * Página de login do Projeto VERITAS.
 *
 * Esta página:
 *
 * 1. Exibe o formulário de login;
 * 2. Recebe o e-mail e a senha pelo método POST;
 * 3. Procura o usuário no banco;
 * 4. Confere a senha;
 * 5. Cria a sessão do usuário;
 * 6. Redireciona para o painel.
 */

// Inclui o arquivo de conexão com o banco de dados.
require_once __DIR__ . '/../config/conexao.php';

// Variável que armazenará possíveis mensagens de erro.
$mensagemErro = '';

// Mantém o e-mail preenchido caso o login apresente erro.
$email = '';

/*
 * Verifica se o formulário foi enviado pelo método POST.
 *
 * Quando a página é apenas aberta, o método será GET.
 * Quando o botão de login for pressionado, será POST.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /*
     * Recebe o e-mail enviado pelo formulário.
     *
     * trim() remove espaços no início e no final.
     * strtolower() transforma o e-mail em letras minúsculas.
     */
    $email = trim(
        strtolower($_POST['email'] ?? '')
    );

    /*
     * Recebe a senha.
     *
     * Não usamos trim() na senha, pois espaços podem fazer
     * parte da senha cadastrada.
     */
    $senha = $_POST['senha'] ?? '';

    /*
     * Verifica se os campos foram preenchidos.
     */
    if ($email === '' || $senha === '') {
        $mensagemErro =
            'Preencha o e-mail e a senha.';
    }

    /*
     * Verifica se o e-mail possui formato válido.
     */
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagemErro =
            'Informe um endereço de e-mail válido.';
    }

    /*
     * Se os dados básicos estiverem corretos,
     * iniciaremos a consulta ao banco.
     */
    else {
        try {
            // Abre a conexão com o banco de dados.
            $pdo = conectarBanco();

            /*
             * Procura um usuário pelo e-mail informado.
             *
             * A consulta preparada evita colocar diretamente
             * o valor digitado pelo usuário dentro do SQL.
             */
            $consulta = $pdo->prepare(
                'SELECT
                    id,
                    nome,
                    email,
                    senha_hash
                 FROM usuarios
                 WHERE email = :email
                 LIMIT 1'
            );

            // Executa a consulta substituindo o parâmetro :email.
            $consulta->execute([
                ':email' => $email
            ]);

            /*
             * Recupera o usuário encontrado.
             *
             * Se não existir nenhum usuário com esse e-mail,
             * o resultado será falso.
             */
            $usuario = $consulta->fetch();

            /*
             * Verifica duas condições:
             *
             * 1. Se o usuário foi encontrado;
             * 2. Se a senha informada corresponde ao hash
             *    armazenado no banco.
             */
            if (
                $usuario
                && password_verify(
                    $senha,
                    $usuario['senha_hash']
                )
            ) {
                /*
                 * Cria um novo identificador de sessão.
                 *
                 * Isso aumenta a segurança depois do login.
                 */
                session_regenerate_id(true);

                /*
                 * Guarda informações do usuário na sessão.
                 *
                 * Esses dados poderão ser utilizados em outras
                 * páginas enquanto o usuário estiver logado.
                 */
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];

                /*
                 * Redireciona para o painel.
                 *
                 * O exit impede que o restante do arquivo
                 * continue sendo executado depois do redirecionamento.
                 */
                header(
                    'Location: index.php?pagina=painel'
                );

                exit;
            }

            /*
             * Usamos uma mensagem genérica.
             *
             * Não informamos se o problema foi no e-mail
             * ou somente na senha.
             */
            $mensagemErro =
                'E-mail ou senha incorretos.';
        } catch (PDOException $erro) {
            /*
             * O erro técnico fica registrado no log do PHP,
             * mas não será exibido diretamente ao usuário.
             */
            error_log(
                'Erro ao realizar login: '
                . $erro->getMessage()
            );

            $mensagemErro =
                'Não foi possível realizar o login. '
                . 'Tente novamente.';
        }
    }
}
?>

<section class="pagina-formulario">
    <div class="container formulario-container">

        <div class="formulario-introducao">
            <span class="etiqueta">
                ACESSO RESTRITO
            </span>

            <h1>
                Entre no seu
                <span>ambiente VERITAS.</span>
            </h1>

            <p>
                Acesse sua conta profissional simulada para consultar
                e organizar os casos cadastrados.
            </p>
        </div>

        <form
            class="formulario"
            method="post"
            action="index.php?pagina=login"
        >
            <?php if ($mensagemErro !== ''): ?>
                <div class="mensagem mensagem-erro">
                    <?= htmlspecialchars(
                        $mensagemErro,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <div class="campo">
                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="advogado@exemplo.com"
                    maxlength="254"
                    required
                >
            </div>

            <div class="campo">
                <label for="senha">
                    Senha
                </label>

                <input
                    type="password"
                    id="senha"
                    name="senha"
                    placeholder="Digite sua senha"
                    required
                >
            </div>

            <button
                type="submit"
                class="botao-principal botao-largo"
            >
                Entrar no sistema
            </button>

            <p class="texto-ajuda">
                Ainda não possui uma conta?

                <a href="index.php?pagina=cadastro">
                    Criar conta
                </a>
            </p>
        </form>

    </div>
</section>
