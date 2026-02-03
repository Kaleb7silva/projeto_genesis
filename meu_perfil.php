<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'config/db.php';
$usuario_id = $_SESSION['usuario_id'];

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fotoPerfil'])) {
    $file = $_FILES['fotoPerfil'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $newName = 'user_' . $usuario_id . '.' . $ext;
            $uploadDir = __DIR__ . '/uploads/fotos/';
            $uploadPath = $uploadDir . $newName;

            // Excluir foto antiga (opcional)
            $stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $oldFoto = $stmt->fetchColumn();
            if ($oldFoto && $oldFoto !== 'default.jpg') {
                $oldPath = $uploadDir . $oldFoto;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Atualiza no banco
                $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                $stmt->execute([$newName, $usuario_id]);

                // Atualiza a sessão
                $_SESSION['foto_perfil'] = $newName;

                $mensagem = "Foto atualizada com sucesso!";
            } else {
                $erro = "Erro ao salvar a imagem.";
            }
        } else {
            $erro = "Formato de imagem não permitido. Use JPG, PNG ou WebP.";
        }
    } else {
        $erro = "Erro no upload da imagem.";
    }
}

// Buscar dados atuais do usuário
$stmt = $pdo->prepare("SELECT nome, email, foto_perfil FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - IBDESPERTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/projeto_genesis/style.css">
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>

    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Meu Perfil</h4>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= (!empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/uploads/fotos/' . $usuario['foto_perfil']))
                            ? '/projeto_genesis/uploads/fotos/' . htmlspecialchars($usuario['foto_perfil'])
                            : './img/utilizador-de-negocios-em-cog_78370-7040.jpg' ?>" class="rounded-circle mb-3"
                            style="width: 120px; height: 120px; object-fit: cover;">

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="fotoPerfil" class="form-label">Alterar foto de perfil</label>
                                <input type="file" class="form-control" id="fotoPerfil" name="fotoPerfil"
                                    accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Atualizar Foto</button>
                        </form>

                        <?php if (!empty($mensagem)): ?>
                            <div class="alert alert-success mt-3"><?= htmlspecialchars($mensagem) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger mt-3"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>

</html>