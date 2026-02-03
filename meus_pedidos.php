<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'config/db.php';
$usuario_id = $_SESSION['usuario_id'];

// Parâmetros de filtro
$filtro = $_GET['filtro'] ?? 'todos';
$data_filtro = $_GET['data_filtro'] ?? '';

// Monta a cláusula WHERE dinamicamente
$where = "WHERE usuario_id = ?";
$params = [$usuario_id];

if ($filtro === 'hoje') {
    $where .= " AND DATE(data_compra) = CURDATE()";
} elseif ($filtro === 'mes') {
    $where .= " AND YEAR(data_compra) = YEAR(CURDATE()) AND MONTH(data_compra) = MONTH(CURDATE())";
} elseif ($filtro === 'data' && $data_filtro) {
    $where .= " AND DATE(data_compra) = ?";
    $params[] = $data_filtro;
}

// Busca histórico de compras com filtro
$stmt_compras = $pdo->prepare("
    SELECT tipo, titulo, quantidade, valor_unitario, total, data_compra
    FROM historico_compras
    $where
    ORDER BY data_compra DESC
");
$stmt_compras->execute($params);
$historico_compras = $stmt_compras->fetchAll();

// Total geral de compras
$total_geral = 0;
foreach ($historico_compras as $h) {
    $total_geral += $h['total'];
}

// Busca agendamentos do membro (sem filtro, por simplicidade)
$stmt_agendamentos = $pdo->prepare("
    SELECT data_hora, motivo, status
    FROM agendamentos
    WHERE usuario_id = ?
    ORDER BY data_hora DESC
");
$stmt_agendamentos->execute([$usuario_id]);
$agendamentos = $stmt_agendamentos->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Meus Pedidos - IBDESPERTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>
    <main class="container mt-4">
        <h2 class="mb-4">
            <i class="fas fa-history me-2"></i>
            Meu Histórico
        </h2>
        <a href="index.php" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>

        <!-- Total Geral -->
        <?php if (!empty($historico_compras)): ?>
            <div class="text-end mb-3">
                <h4 class="text-success">
                    <strong>Total Gasto: R$ <?= number_format($total_geral, 2, ',', '.') ?></strong>
                </h4>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <form method="GET" class="mb-4">
            <div class="row g-2">
                <div class="col-md-3">
                    <select name="filtro" class="form-select" onchange="this.form.submit()">
                        <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos os períodos</option>
                        <option value="hoje" <?= $filtro === 'hoje' ? 'selected' : '' ?>>Hoje</option>
                        <option value="mes" <?= $filtro === 'mes' ? 'selected' : '' ?>>Este mês</option>
                        <option value="data" <?= $filtro === 'data' ? 'selected' : '' ?>>Data específica</option>
                    </select>
                </div>
                <?php if ($filtro === 'data'): ?>
                    <div class="col-md-3">
                        <input type="date" name="data_filtro" class="form-control" value="<?= htmlspecialchars($data_filtro) ?>" onchange="this.form.submit()">
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <!-- Histórico de Compras -->
        <h3><i class="fas fa-shopping-cart me-2"></i> Compras</h3>
        <?php if (empty($historico_compras)): ?>
            <div class="alert alert-info">Nenhum registro de compra encontrado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário</th>
                            <th>Total</th>
                            <th>Data da Compra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_compras as $h): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= $h['tipo'] === 'evento' ? 'primary' : 'warning' ?>">
                                        <?= ucfirst($h['tipo']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($h['titulo']) ?></td>
                                <td><?= (int) $h['quantidade'] ?></td>
                                <td>R$ <?= number_format($h['valor_unitario'], 2, ',', '.') ?></td>
                                <td><strong>R$ <?= number_format($h['total'], 2, ',', '.') ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($h['data_compra'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Histórico de Agendamentos -->
        <h3 class="mt-5"><i class="fas fa-calendar-check me-2"></i> Agendamentos</h3>
        <?php if (empty($agendamentos)): ?>
            <div class="alert alert-info">Nenhum agendamento encontrado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Motivo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $a): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                                <td><?= htmlspecialchars($a['motivo']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $a['status'] === 'confirmado' ? 'success' : ($a['status'] === 'pendente' ? 'warning' : 'danger') ?>">
                                        <?= ucfirst($a['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>

</html>