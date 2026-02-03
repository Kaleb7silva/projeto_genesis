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
$stmt = $pdo->query("SELECT * FROM eventos ORDER BY data_evento DESC");
$eventos = $stmt->fetchAll();
?>

<?php require __DIR__ . '/../includes/nav.php'; ?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-calendar-alt text-success me-2"></i> Gerenciamento de Eventos
        </h2>
        <a href="novo.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Novo Evento
        </a>
    </div>

    <div class="row g-4">
        <?php if (empty($eventos)): ?>
            <div class="col-12">
                <p class="text-muted">Nenhum evento cadastrado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($eventos as $e): ?>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100">
                    <img src="<?= htmlspecialchars($e['imagem'] ?: '/projeto_genesis/img/default.jpg') ?>" 
                         alt="<?= htmlspecialchars($e['titulo']) ?>" 
                         class="card-img-top" style="height: 180px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($e['titulo']) ?></h5>
                        <p class="card-text text-muted small"><?= htmlspecialchars(substr($e['descricao'], 0, 60)) ?></p>
                        <p class="card-text"><small class="text-muted">Local: <?= htmlspecialchars($e['local']) ?></small></p>
                        <p class="card-text"><small class="text-muted">Data: <?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></small></p>
                        <p class="card-text"><small class="text-muted">Preço: R$ <?= number_format($e['preco_ingresso'], 2, ',', '.') ?></small></p>
                        <div class="mt-auto d-flex gap-1">
    <!-- Botão Publicar/Despublicar -->
    <?php if ($e['publicado']): ?>
        <a href="publicar.php?id=<?= $e['id'] ?>&acao=despublicar" 
           class="btn btn-sm btn-outline-warning"
           title="Tornar não publicado">
            <i class="fas fa-eye-slash"></i>
        </a>
    <?php else: ?>
        <a href="publicar.php?id=<?= $e['id'] ?>&acao=publicar" 
           class="btn btn-sm btn-outline-success"
           title="Publicar no site">
            <i class="fas fa-eye"></i>
        </a>
    <?php endif; ?>

    <!-- Editar -->
    <a href="editar.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-edit"></i>
    </a>

    <!-- Excluir -->
    <a href="excluir.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger"
       onclick="return confirm('Excluir este evento?')">
        <i class="fas fa-trash"></i>
    </a>
</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>