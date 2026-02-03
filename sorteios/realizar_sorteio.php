<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$sorteio_id = (int) ($_POST['sorteio_id'] ?? 0);
$tipo = $_POST['tipo'] ?? 'membros';
$quantidade = min(1000000, max(1, (int) ($_POST['quantidade'] ?? 100)));
$sequencial = isset($_POST['sequencial']);

if (!$sorteio_id) {
    echo json_encode(['success' => false, 'message' => 'Sorteio inválido.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM sorteios WHERE id = ? AND publicado = 1");
$stmt->execute([$sorteio_id]);
$sorteio = $stmt->fetch();

if (!$sorteio) {
    echo json_encode(['success' => false, 'message' => 'Sorteio não encontrado ou não publicado.']);
    exit;
}

try {
    if ($tipo === 'membros') {
        $stmt_membros = $pdo->prepare("SELECT id, nome FROM usuarios WHERE perfil = 'membro' AND status = 'ativo'");
        $stmt_membros->execute();
        $membros = $stmt_membros->fetchAll();
        if (empty($membros)) {
            throw new Exception("Nenhum membro ativo.");
        }
        $sorteado = $membros[array_rand($membros)];
        $pdo->prepare("UPDATE sorteios SET vencedor_membro_id = ?, numero_vencedor = NULL, resultado_exibido = 1 WHERE id = ?")
            ->execute([$sorteado['id'], $sorteio_id]);
        $mensagem = "🎉 Vencedor: <strong>{$sorteado['nome']}</strong>";
    } else {
        if ($sequencial) {
            $sorteado = rand(1, $quantidade);
        } else {
            $sorteado = rand(1, $quantidade);
        }
        $pdo->prepare("UPDATE sorteios SET numero_vencedor = ?, vencedor_membro_id = NULL, resultado_exibido = 1 WHERE id = ?")
            ->execute([$sorteado, $sorteio_id]);
        $mensagem = "🎉 Número sorteado: <strong>{$sorteado}</strong>";
    }

    echo json_encode(['success' => true, 'message' => $mensagem]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>