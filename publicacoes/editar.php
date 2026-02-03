<?php
session_start();
require __DIR__ . '/../config/db.php';

// Verifica se é admin
if ($_SESSION['perfil'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Obtém o ID da publicação
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM publicacoes WHERE id = ?");
$stmt->execute([$id]);
$pub = $stmt->fetch();

if (!$pub) {
    header('Location: index.php');
    exit;
}

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = $_POST['tipo'] ?? 'imagem';
    $conteudo = '';
    $arquivo = $pub['arquivo']; // mantém o atual
    $link_video = '';

    if ($tipo === 'video' && !empty($_POST['link_video'])) {
        $link_video = trim($_POST['link_video']);
        if (filter_var($link_video, FILTER_VALIDATE_URL)) {
            $arquivo = $link_video;
        } else {
            die("Link de vídeo inválido.");
        }
    } elseif (!empty($_FILES['arquivo']['name'])) {
        $nome_original = $_FILES['arquivo']['name'];
        $ext = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
        $tipos_validos = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp4', 'webm', 'mov'];

        if (!in_array($ext, $tipos_validos)) {
            die("Tipo de arquivo não permitido.");
        }

        $nome = uniqid() . '.' . $ext;
        $destino = "../uploads/publicacoes/$nome";

        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            $arquivo = "/projeto_genesis/uploads/publicacoes/$nome";
        } else {
            die("Erro ao fazer upload do arquivo.");
        }
    }

    // Atualiza no banco
    $stmt = $pdo->prepare("UPDATE publicacoes SET titulo = ?, conteudo = ?, tipo = ?, arquivo = ? WHERE id = ?");
    $stmt->execute([$titulo, $conteudo, $tipo, $arquivo, $id]);

    header('Location: index.php?sucesso=1');
    exit;
}

// Inclui a navegação após a lógica PHP (evita erros de header)
require __DIR__ . '/../includes/nav.php';
?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold">Editar Publicação</h2>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-12">
            <label class="form-label">Título</label>
            <input name="titulo" value="<?= htmlspecialchars($pub['titulo']) ?>" required class="form-control">
        </div>

        <div class="col-12">
            <label class="form-label">Tipo</label>
            <select name="tipo" required class="form-select" id="tipo-publicacao">
                <option value="imagem" <?= $pub['tipo'] === 'imagem' ? 'selected' : '' ?>>Imagem</option>
                <option value="pdf" <?= $pub['tipo'] === 'pdf' ? 'selected' : '' ?>>PDF</option>
                <option value="video" <?= $pub['tipo'] === 'video' ? 'selected' : '' ?>>Vídeo</option>
            </select>
        </div>

        <!-- Campo Link do Vídeo -->
        <div class="col-12" id="campo-link-video" style="display: <?= $pub['tipo'] === 'video' ? 'block' : 'none' ?>;">
            <label class="form-label">Link do Vídeo (YouTube ou Vimeo)</label>
            <input type="url" name="link_video" class="form-control"
                value="<?= strpos($pub['arquivo'], 'http') === 0 ? htmlspecialchars($pub['arquivo']) : '' ?>"
                placeholder="https://www.youtube.com/watch?v=...">
            <small class="form-text text-muted">Ou cole um link do YouTube/Vimeo.</small>
        </div>

        <!-- Campo Arquivo -->
        <div class="col-12" id="campo-arquivo">
            <label class="form-label">Arquivo Atual</label>
            <?php if ($pub['tipo'] === 'imagem'): ?>
                <img src="<?= htmlspecialchars($pub['arquivo']) ?>" class="img-fluid rounded mb-2"
                    style="max-height: 150px; object-fit: cover; width: 100%;">
            <?php elseif ($pub['tipo'] === 'pdf'): ?>
                <a href="<?= htmlspecialchars($pub['arquivo']) ?>" target="_blank" class="btn btn-outline-primary">Ver
                    PDF</a>
            <?php elseif ($pub['tipo'] === 'video'): ?>
                <a href="<?= htmlspecialchars($pub['arquivo']) ?>" target="_blank" class="btn btn-outline-primary">Ver
                    Vídeo</a>
            <?php endif; ?>
            <div class="mt-2">
                <label class="form-label">Novo Arquivo (opcional)</label>
                <input type="file" name="arquivo" class="form-control">
                <small class="form-text text-muted">Se você usar um link acima, este campo será ignorado.</small>
            </div>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-warning">Salvar Alterações</button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.getElementById('tipo-publicacao').addEventListener('change', function () {
        const tipo = this.value;
        const campoLink = document.getElementById('campo-link-video');
        const campoArquivo = document.getElementById('campo-arquivo');

        if (tipo === 'video') {
            campoLink.style.display = 'block';
            campoArquivo.style.display = 'block';
        } else {
            campoLink.style.display = 'none';
            campoArquivo.style.display = 'block';
        }
    });
</script>