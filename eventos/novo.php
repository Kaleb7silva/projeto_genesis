<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /projeto_genesis/login.php');
    exit;
}
$perfil = $_SESSION['perfil'] ?? 'membro';
if ($perfil !== 'admin') {
    header('Location: /projeto_genesis/index.php');
    exit;
}

require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data = $_POST['data_evento'] ?? '';
    $local = trim($_POST['local'] ?? '');
    $preco_ingresso = (float)($_POST['preco_ingresso'] ?? 0);

    // Imagem padrão
    $imagem = "/projeto_genesis/img/default.jpg";

    // Processa upload de imagem
    if (!empty($_FILES['imagem']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $nome = uniqid() . '.' . $ext;
            $upload_dir = "../uploads/eventos/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $nome)) {
                $imagem = "/projeto_genesis/uploads/eventos/$nome";
            }
        }
    }

    // Insere no banco
    $pdo->prepare("INSERT INTO eventos (titulo, descricao, data_evento, local, imagem, preco_ingresso, publicado) 
                   VALUES (?, ?, ?, ?, ?, ?, 1)")
        ->execute([$titulo, $descricao, $data, $local, $imagem, $preco_ingresso]);

    header('Location: index.php?sucesso=1');
    exit;
}
?>

<?php require __DIR__ . '/../includes/nav.php'; ?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold">Novo Evento</h2>
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
            <label class="form-label">Data e Hora</label>
            <input type="datetime-local" name="data_evento" required class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Local</label>
            <input name="local" required class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Preço do Ingresso (R$)</label>
            <input type="number" name="preco_ingresso" step="0.01" value="0" min="0" required class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Imagem (opcional)</label>
            <input type="file" name="imagem" accept="image/*" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">Salvar</button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>