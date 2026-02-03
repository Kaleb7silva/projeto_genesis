<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

require 'config/db.php';

$id = $_POST['id'] ?? 0;
$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$genero = $_POST['genero'] ?? 'outro';
$batizado = $_POST['batizado'] ?? 'nao';
$status = $_POST['status'] ?? 'ativo';
$foto_perfil = '';

// Busca membro atual
$stmt = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ? AND perfil = 'membro'");
$stmt->execute([$id]);
$membro = $stmt->fetch();
if (!$membro) {
    echo json_encode(['success' => false, 'message' => 'Membro não encontrado.']);
    exit;
}

// Se houver nova foto via base64
if (isset($_POST['foto_cortada']) && !empty($_POST['foto_cortada'])) {
    $base64 = $_POST['foto_cortada'];
    if (strpos($base64, 'data:image/') === 0) {
        list($type, $data) = explode(';', $base64);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);

        $extensao = 'jpg';
        if (strpos($type, 'png') !== false) {
            $extensao = 'png';
        }

        $upload_dir = __DIR__ . '/uploads/fotos/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0755, true);

        $nome_arquivo = uniqid('perfil_', true) . '.' . $extensao;
        $caminho_destino = $upload_dir . $nome_arquivo;

        if (file_put_contents($caminho_destino, $data)) {
            // Apaga foto antiga (se não for padrão)
            if ($membro['foto_perfil'] !== 'default.png') {
                $caminho_antigo = $upload_dir . $membro['foto_perfil'];
                if (file_exists($caminho_antigo))
                    unlink($caminho_antigo);
            }
            $foto_perfil = $nome_arquivo;
        }
    }
}

// Se não houver nova foto, mantém a atual
if (!$foto_perfil) {
    $foto_perfil = $membro['foto_perfil'];
}

// Validação básica
if (strlen($nome) < 2) {
    echo json_encode(['success' => false, 'message' => 'Nome muito curto.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido.']);
    exit;
}

// Atualiza no banco
$stmt = $pdo->prepare("
    UPDATE usuarios 
    SET nome = ?, email = ?, genero = ?, batizado = ?, status = ?, foto_perfil = ? 
    WHERE id = ?
");
$stmt->execute([$nome, $email, $genero, $batizado, $status, $foto_perfil, $id]);

// Retorna URL da nova foto
$foto_url = '/projeto_genesis/uploads/fotos/' . $foto_perfil;
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $foto_url)) {
    $foto_url = '/projeto_genesis/img/utilizador-de-negocios-em-cog_78370-7040.jpg';
}

echo json_encode([
    'success' => true,
    'message' => 'Alterações salvas.',
    'foto_url' => $foto_url
]);
?>