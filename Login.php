<?php
session_start();

// Redireciona se já estiver logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require 'config/db.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['password'];

    // Busca o usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Verifica se existe e se a senha bate
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['perfil'] = $usuario['perfil'];
        $_SESSION['foto_perfil'] = $usuario['foto_perfil'];
        $_SESSION['nome'] = $usuario['nome'];

        header('Location: index.php');
        exit;
    } else {
        $erro = "E-mail ou senha inválidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>IBDESPERTAR - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-bg">
        <div class="login-overlay"></div>
        <div class="login-container">
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="img/LOGOTIPO NOME PRETO PNG (1).png" alt="Logo IBDESPERTAR" class="logo">
                    <h2 class="fw-bold text-black">Acesse sua Conta</h2>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger mb-3">
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label text-black">E-mail</label>
                        <input type="email" id="email" name="email" required
                            class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <div class="invalid-feedback">Por favor, informe um e-mail válido.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label text-black">Senha</label>
                        <input type="password" id="password" name="password" required
                            class="form-control">
                        <div class="invalid-feedback">Por favor, informe sua senha.</div>
                    </div>
                    <button type="submit" class="btn btn-login w-100 mt-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Entrar
                    </button>
                </form>

                <div class="mt-3 text-center">
                    <a href="#" class="forgot-password">Esqueceu sua senha?</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação do formulário
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>

</html>