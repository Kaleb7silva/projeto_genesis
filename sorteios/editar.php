<?php
session_start();

// ✅ 1. Processar o formulário ANTES de qualquer saída
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

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sorteios WHERE id = ?");
$stmt->execute([$id]);
$sorteio = $stmt->fetch();

if (!$sorteio) {
    header('Location: index.php');
    exit;
}

// ✅ Processar o POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data = $_POST['data_sorteio'] ?? '';
    $valor_rifa = (float) ($_POST['valor_rifa'] ?? 0);
    $imagem = $sorteio['imagem']; // mantém a atual

    // Upload de nova imagem
    if (!empty($_FILES['imagem']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            // Remove a antiga (se não for padrão)
            $caminho_antigo = __DIR__ . '/../' . ltrim($sorteio['imagem'], '/');
            if (file_exists($caminho_antigo) && !str_contains($sorteio['imagem'], 'default.jpg')) {
                unlink($caminho_antigo);
            }
            $nome = uniqid() . '.' . $ext;
            $upload_dir = "../uploads/sorteios/";
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_dir . $nome)) {
                $imagem = "/projeto_genesis/uploads/sorteios/$nome";
            }
        }
    }

    // Atualiza no banco
    $pdo->prepare("UPDATE sorteios SET titulo = ?, descricao = ?, data_sorteio = ?, imagem = ?, valor_rifa = ? WHERE id = ?")
        ->execute([$titulo, $descricao, $data, $imagem, $valor_rifa, $id]);

    // ✅ REDIRECIONA AQUI — ainda não houve saída!
    header('Location: index.php?sucesso=1');
    exit;
}
?>

<!-- ✅ SÓ AGORA incluímos o HTML/navigation -->
<?php require __DIR__ . '/../includes/nav.php'; ?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold">Editar Sorteio</h2>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-12">
            <label class="form-label">Título</label>
            <input name="titulo" value="<?= htmlspecialchars($sorteio['titulo']) ?>" required class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Descrição</label>
            <textarea name="descricao" class="form-control"
                rows="3"><?= htmlspecialchars($sorteio['descricao']) ?></textarea>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Data do Sorteio</label>
            <input type="date" name="data_sorteio" value="<?= $sorteio['data_sorteio'] ?>" required
                class="form-control">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Preço do Sorteio (R$)</label>
            <input type="number" name="valor_rifa" step="0.01"
                value="<?= htmlspecialchars($sorteio['valor_rifa'] ?? 0) ?>" min="0" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Imagem Atual</label>
            <img src="<?= htmlspecialchars($sorteio['imagem']) ?>" class="img-fluid rounded mb-2"
                style="max-height: 200px; object-fit: cover;">
        </div>
        <div class="col-12">
            <label class="form-label">Nova Imagem (opcional)</label>
            <input type="file" name="imagem" accept="image/*" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-purple">Atualizar</button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>