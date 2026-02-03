<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    header('Location: /projeto_genesis/index.php');
    exit;
}
require __DIR__ . '/../config/db.php';

// Processar o formulário PRIMEIRO, antes de qualquer saída HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $data = $_POST['data_sorteio'];
    $valor_rifa = $_POST['valor_rifa'] ?? 0;

    // Imagem padrão
    $imagem = '/projeto_genesis/img/default.jpg';

    // Processa upload de imagem
    if (!empty($_FILES['imagem']['name'])) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $nome = uniqid() . '.' . $ext;
            $upload_dir = "../uploads/sorteios/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $nome)) {
                $imagem = "/projeto_genesis/uploads/sorteios/$nome";
            }
        }
    }

    // Verifica se já existe um sorteio com o mesmo título e data
    $stmt_check = $pdo->prepare("SELECT id FROM sorteios WHERE titulo = ? AND data_sorteio = ?");
    $stmt_check->execute([$titulo, $data]);
    $existe = $stmt_check->fetch();

    if ($existe) {
        // Se já existir, redireciona com mensagem de erro
        header('Location: index.php?erro=duplicado');
        exit;
    }

    // Insere no banco
    $pdo->prepare("INSERT INTO sorteios (titulo, descricao, data_sorteio, imagem, valor_rifa) VALUES (?, ?, ?, ?, ?)")
        ->execute([$titulo, $descricao, $data, $imagem, $valor_rifa]);

    // Redireciona com sucesso
    header('Location: index.php?sucesso=1');
    exit; // <- IMPORTANTE: interrompe a execução aqui
}

// A partir daqui, o código HTML pode ser executado
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo Sorteio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="/projeto_genesis/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/nav.php'; ?>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-2xl font-bold">Novo Sorteio</h2>
            <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-12">
                <label class="form-label">Título</label>
                <input name="titulo" required class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Data do Sorteio</label>
                <input type="date" name="data_sorteio" required class="form-control">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Preço do Sorteio (R$)</label>
                <input type="number" name="valor_rifa" step="0.01" value="0" min="0" required class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Imagem (opcional)</label>
                <input type="file" name="imagem" accept="image/*" class="form-control">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-purple">Salvar</button>
            </div>
        </form>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>