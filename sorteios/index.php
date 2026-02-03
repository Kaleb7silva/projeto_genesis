<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: /projeto_genesis/login.php');
    exit;
}
require __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT * FROM sorteios ORDER BY data_sorteio DESC");
$sorteios = $stmt->fetchAll();
?>

<?php require __DIR__ . '/../includes/nav.php'; ?>

<?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show">
        <?= $_SESSION['alert']['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['alert']); ?>
<?php endif; ?>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-gift text-purple me-2"></i> Gerenciamento de Sorteios
        </h2>
        <div class="d-flex gap-2">
            <a href="novo.php" class="btn btn-purple">
                <i class="fas fa-plus me-1"></i> Novo Sorteio
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSortear">
                <i class="fas fa-dice me-1"></i> Sortear
            </button>
        </div>
    </div>

    <!-- Modal de Sorteio -->
    <div class="modal fade" id="modalSortear" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Realizar Sorteio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formSortear">
                        <div class="mb-3">
                            <label class="form-label">Sorteio</label>
                            <select name="sorteio_id" class="form-select" required>
                                <option value="">-- Selecione --</option>
                                <?php
                                $stmt_ativos = $pdo->prepare("SELECT id, titulo, data_sorteio FROM sorteios WHERE data_sorteio >= CURDATE() AND publicado = 1 ORDER BY data_sorteio");
                                $stmt_ativos->execute();
                                while ($s = $stmt_ativos->fetch()):
                                    ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['titulo']) ?>
                                        (<?= date('d/m/Y', strtotime($s['data_sorteio'])) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de Sorteio</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo" value="membros"
                                        id="tipo_membros" checked>
                                    <label class="form-check-label" for="tipo_membros">Por Membros</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo" value="numeros"
                                        id="tipo_numeros">
                                    <label class="form-check-label" for="tipo_numeros">Por Números</label>
                                </div>
                            </div>
                        </div>
                        <div id="campo_quantidade" class="mb-3 d-none">
                            <label class="form-label">Quantidade de Números (máx. 1.000.000)</label>
                            <input type="number" name="quantidade" class="form-control" min="1" max="1000000"
                                value="100">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="sequencial" id="sequencial">
                                <label class="form-check-label" for="sequencial">Ordem crescente (1, 2, 3...)</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarSorteio">Confirmar Sorteio</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sorteios cadastrados -->
    <?php if (empty($sorteios)): ?>
        <p class="text-muted">Nenhum sorteio cadastrado.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($sorteios as $s): ?>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100 d-flex flex-column">
                        <img src="<?= htmlspecialchars($s['imagem'] ?: '/projeto_genesis/img/default.jpg') ?>"
                            alt="<?= htmlspecialchars($s['titulo']) ?>" class="card-img-top"
                            style="height: 160px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?= htmlspecialchars($s['titulo']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?= substr(htmlspecialchars($s['descricao']), 0, 60) ?>...
                            </p>
                            <p class="card-text"><small class="text-muted">Data:
                                    <?= htmlspecialchars($s['data_sorteio']) ?></small></p>
                            <p class="card-text"><small class="text-muted">Preço: R$
                                    <?= number_format($s['valor_rifa'], 2, ',', '.') ?></small></p>
                            <div class="mt-auto d-flex justify-content-between gap-1">
                                <?php if ($s['publicado']): ?>
                                    <a href="publicar.php?id=<?= $s['id'] ?>&acao=despublicar"
                                        class="btn btn-sm btn-outline-warning" title="Tornar não publicado">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="publicar.php?id=<?= $s['id'] ?>&acao=publicar" class="btn btn-sm btn-outline-success"
                                        title="Publicar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="editar.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <a href="excluir.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Excluir este sorteio?')">Excluir</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Resultados dos Sorteios Realizados -->
    <div class="col-12 mt-5">
        <h3>🏆 Resultados dos Sorteios</h3>
        <?php
        $stmt_resultados = $pdo->prepare("
        SELECT s.*, u.nome AS vencedor_nome 
        FROM sorteios s 
        LEFT JOIN usuarios u ON s.vencedor_membro_id = u.id 
        WHERE s.resultado_exibido = 1 
        ORDER BY s.data_sorteio DESC
        LIMIT 3
    ");
        $stmt_resultados->execute();
        $resultados = $stmt_resultados->fetchAll();

        if (empty($resultados)): ?>
            <p class="text-muted">Nenhum sorteio realizado ainda.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($resultados as $r): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-success">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-success"><?= htmlspecialchars($r['titulo']) ?></h5>
                                <p class="card-text text-muted small"><?= substr(htmlspecialchars($r['descricao']), 0, 60) ?>...
                                </p>
                                <p class="card-text"><small class="text-muted">Data:
                                        <?= date('d/m/Y', strtotime($r['data_sorteio'])) ?></small></p>
                                <?php if ($r['vencedor_membro_id']): ?>
                                    <p class="card-text"><strong>Vencedor:</strong> <?= htmlspecialchars($r['vencedor_nome']) ?></p>
                                <?php elseif ((int) $r['numero_vencedor'] > 0): ?>
                                    <p class="card-text"><strong>Número Vencedor:</strong> <?= (int) $r['numero_vencedor'] ?></p>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <a href="editar.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Detalhes</a>
                                    <a href="excluir.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger ms-2"
                                        onclick="return confirm('Excluir este resultado de sorteio?');">
                                        Excluir
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.querySelectorAll('input[name="tipo"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const tipo = document.querySelector('input[name="tipo"]:checked').value;
            document.getElementById('campo_quantidade').classList.toggle('d-none', tipo !== 'numeros');
        });
    });

    document.getElementById('btnConfirmarSorteio').addEventListener('click', async () => {
        const formData = new FormData(document.getElementById('formSortear'));
        const res = await fetch('realizar_sorteio.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    });
</script>