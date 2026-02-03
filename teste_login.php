<?php
require 'config/db.php';

$email = 'admin@ibdespertar.org';
$senha = 'admin123';

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario) {
    echo "Usuário encontrado!<br>";
    echo "Senha bate? " . (password_verify($senha, $usuario['senha']) ? '<span style="color:green">SIM</span>' : '<span style="color:red">NÃO</span>');
} else {
    echo "Usuário NÃO encontrado.";
}
?>