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
$stmt = $pdo->query("SELECT * FROM publicacoes ORDER BY data_publicacao DESC");
$publicacoes = $stmt->fetchAll();
?>

<?php require __DIR__ . '/../includes/nav.php'; ?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-file-alt text-warning me-2"></i> Gerenciamento de Publicações
        </h2>
        <a href="novo.php" class="btn btn-warning">
            <i class="fas fa-plus me-1"></i> Nova Publicação
        </a>
    </div>

    <?php if (empty($publicacoes)): ?>
        <div class="col-12">
            <p class="text-muted">Nenhuma publicação cadastrada.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($publicacoes as $p): ?>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100 d-flex flex-column">
                        <div class="card-body d-flex flex-column flex-grow-1">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($p['titulo']) ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-<?= match ($p['tipo']) {
                                                        'imagem' => 'image text-primary',
                                                        'pdf'    => 'file-pdf text-danger',
                                                        'video'  => 'video text-purple',
                                                        default  => 'file text-muted'
                                                    } ?>"></i>
                                <?= ucfirst(htmlspecialchars($p['tipo'])) ?>
                            </p>
                            <p class="card-text text-muted small mt-auto">
                                Publicado em: <?= date('d/m/Y H:i', strtotime($p['data_publicacao'])) ?>
                            </p>

                            <!-- Conteúdo dinâmico -->
                            <div class="mt-2 flex-grow-1 position-relative" style="width: 100%; height: auto; min-height: 150px;">
                                <?php if ($p['tipo'] === 'imagem'): ?>
                                    <!-- Imagem: exibida diretamente -->
                                    <img src="<?= htmlspecialchars($p['arquivo']) ?>"
                                        alt="<?= htmlspecialchars($p['titulo']) ?>"
                                        class="img-fluid w-100 h-100"
                                        style="object-fit: cover; object-position: center;">

                                <?php elseif ($p['tipo'] === 'pdf'): ?>
                                    <!-- PDF: botão "Ver" -->
                                    <a href="<?= htmlspecialchars($p['arquivo']) ?>" target="_blank"
                                        class="btn btn-sm btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-file-pdf me-1"></i> Ver PDF
                                    </a>

                                <?php elseif ($p['tipo'] === 'video'): ?>
                                    <!-- Vídeo: reprodução automática -->
                                    <?php
                                    // Detecta se é um link do YouTube
                                    $is_youtube = strpos($p['arquivo'], 'youtube.com') !== false || strpos($p['arquivo'], 'youtu.be') !== false;
                                    ?>
                                    <?php if ($is_youtube): ?>
                                        <!-- YouTube: iframe -->
                                        <div class="position-absolute top-0 start-0 w-100 h-100">
                                            <?php
                                            // Extrai o ID do vídeo do YouTube
                                            $video_id = '';
                                            if (strpos($p['arquivo'], 'v=') !== false) {
                                                $video_id = explode('v=', $p['arquivo'])[1];
                                                $video_id = explode('&', $video_id)[0];
                                            } elseif (strpos($p['arquivo'], 'youtu.be/') !== false) {
                                                $video_id = explode('youtu.be/', $p['arquivo'])[1];
                                                $video_id = explode('?', $video_id)[0];
                                            }
                                            ?>
                                            <?php if ($video_id): ?>
                                                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>?autoplay=1&mute=1&loop=1&controls=0"
                                                    title="Vídeo do YouTube"
                                                    allowfullscreen
                                                    class="w-100 h-100"
                                                    style="border: none; aspect-ratio: 16/9;"></iframe>
                                            <?php else: ?>
                                                <p class="text-muted">ID do vídeo inválido.</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <!-- Vídeo local (MP4, WebM, MOV): tag <video> com autoplay -->
                                        <video controls autoplay muted loop class="w-100 h-100" style="object-fit: cover; aspect-ratio: 16/9;">
                                            <source src="<?= htmlspecialchars($p['arquivo']) ?>"
                                                type="video/<?= pathinfo($p['arquivo'], PATHINFO_EXTENSION) ?>">
                                            Seu navegador não suporta vídeo.
                                        </video>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </div>

                            <!-- Ações -->
                            <div class="mt-2 d-flex gap-1">
                                <!-- Publicar/Despublicar -->
                                <?php if ($p['publicado']): ?>
                                    <a href="publicar.php?id=<?= $p['id'] ?>&acao=despublicar"
                                        class="btn btn-sm btn-outline-warning" title="Tornar não publicado">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="publicar.php?id=<?= $p['id'] ?>&acao=publicar"
                                        class="btn btn-sm btn-outline-success" title="Publicar no site">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>

                                <a href="editar.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="excluir.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Excluir esta publicação?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div> <!-- Fecha .row -->
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>