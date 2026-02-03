<?php
// 1. Inicia sessão e verifica permissões PRIMEIRO
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /projeto_genesis/login.php');
    exit;
}
$perfil = $_SESSION['perfil'] ?? 'membro';
$usuario_id = $_SESSION['usuario_id'];
if ($perfil !== 'admin') {
    header('Location: /projeto_genesis/index.php');
    exit;
}

// 2. Processa o formulário
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo = $_POST['tipo'] ?? 'imagem';
    $conteudo = ''; // ou use um campo de texto se quiser
    $arquivo = '';
    $link_video = '';

    if ($tipo === 'video' && !empty($_POST['link_video'])) {
        // Se for vídeo e o usuário colocou um link, usa o link
        $link_video = trim($_POST['link_video']);
        if (filter_var($link_video, FILTER_VALIDATE_URL)) {
            $arquivo = $link_video; // Salva o link como "arquivo"
        } else {
            die("Link de vídeo inválido.");
        }
    } elseif (!empty($_FILES['arquivo']['name'])) {
        // Caso contrário, faz upload do arquivo
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

    // 3. Insere com TODOS os campos necessários
    $pdo->prepare("INSERT INTO publicacoes (titulo, conteudo, tipo, arquivo, data_publicacao, criador_id) 
                   VALUES (?, ?, ?, ?, NOW(), ?)")
        ->execute([$titulo, $conteudo, $tipo, $arquivo, $usuario_id]);

    header('Location: index.php?sucesso=1');
    exit;
}
?>

<?php require __DIR__ . '/../includes/nav.php'; ?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold">Nova Publicação</h2>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-12">
            <label class="form-label">Título</label>
            <input name="titulo" required class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Tipo</label>
            <select name="tipo" required class="form-select" id="tipo-publicacao">
                <option value="imagem">Imagem</option>
                <option value="pdf">PDF</option>
                <option value="video">Vídeo</option>
            </select>
        </div>

        <!-- Campo Link do Vídeo (só aparece se tipo for "video") -->
        <div class="col-12" id="campo-link-video" style="display: none;">
            <label class="form-label">Link do Vídeo (YouTube ou Vimeo)</label>
            <input type="url" name="link_video" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
            <small class="form-text text-muted">Ou cole um link do YouTube/Vimeo.</small>
        </div>

        <!-- Campo Arquivo (aparece para todos, mas é opcional para vídeos com link) -->
        <div class="col-12" id="campo-arquivo">
            <label class="form-label">Arquivo</label>
            <input type="file" name="arquivo" class="form-control">
            <small class="form-text text-muted">
                Para vídeos, você pode enviar arquivos MP4, WebM, MOV ou colar um link acima.
            </small>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-warning">Salvar</button>
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