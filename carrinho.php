<?php
session_start();
require 'config/db.php';

// Função para salvar o carrinho na sessão e no cookie
function salvarCarrinho($carrinho)
{
    $_SESSION['carrinho'] = $carrinho;
    setcookie('carrinho_persistente', json_encode($carrinho), time() + 30 * 24 * 60 * 60, '/', '', false, true);
}

// Inicializa carrinho no novo formato: ['eventos' => [id => qtd], 'sorteios' => [id => qtd]]
if (!isset($_SESSION['carrinho'])) {
    if (isset($_COOKIE['carrinho_persistente'])) {
        $carrinho_cookie = json_decode($_COOKIE['carrinho_persistente'], true);
        if (is_array($carrinho_cookie) && isset($carrinho_cookie['eventos']) && isset($carrinho_cookie['sorteios'])) {
            // Se for o formato antigo (array de IDs), converte para id => quantidade
            if (!is_array(current($carrinho_cookie['eventos'] ?? [1 => 1]))) {
                $eventos = [];
                foreach ($carrinho_cookie['eventos'] as $id)
                    $eventos[(int) $id] = 1;
                $sorteios = [];
                foreach ($carrinho_cookie['sorteios'] as $id)
                    $sorteios[(int) $id] = 1;
                $_SESSION['carrinho'] = ['eventos' => $eventos, 'sorteios' => $sorteios];
            } else {
                $_SESSION['carrinho'] = $carrinho_cookie;
            }
        } else {
            $_SESSION['carrinho'] = ['eventos' => [], 'sorteios' => []];
        }
    } else {
        $_SESSION['carrinho'] = ['eventos' => [], 'sorteios' => []];
    }
}

// Adicionar item (do index.php, por exemplo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['atualizar_quantidade'])) {
    $tipo = $_POST['tipo'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }

    $carrinho = $_SESSION['carrinho'];

    if ($tipo === 'evento') {
        $stmt = $pdo->prepare("SELECT id, preco_ingresso FROM eventos WHERE id = ? AND publicado = 1");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            if (!isset($carrinho['eventos'][$id])) {
                $carrinho['eventos'][$id] = 1;
                salvarCarrinho($carrinho);
            }
        }
    } elseif ($tipo === 'sorteio') {
        $stmt = $pdo->prepare("SELECT id, valor_rifa FROM sorteios WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            if (!isset($carrinho['sorteios'][$id])) {
                $carrinho['sorteios'][$id] = 1;
                salvarCarrinho($carrinho);
            }
        }
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Atualizar quantidade (via formulário de + / -)
if (isset($_POST['atualizar_quantidade'])) {
    $tipo = $_POST['tipo'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $qtd = max(1, (int) ($_POST['quantidade'] ?? 1));

    $carrinho = $_SESSION['carrinho'];

    if ($tipo === 'evento' && $id > 0) {
        $carrinho['eventos'][$id] = $qtd;
    } elseif ($tipo === 'sorteio' && $id > 0) {
        $carrinho['sorteios'][$id] = $qtd;
    }

    salvarCarrinho($carrinho);
    header('Location: carrinho.php');
    exit;
}

// Remover item
if (isset($_GET['remover'])) {
    $tipo = $_GET['tipo'] ?? '';
    $id = (int) ($_GET['id'] ?? 0);
    $carrinho = $_SESSION['carrinho'];

    if ($tipo === 'evento' && isset($carrinho['eventos'][$id])) {
        unset($carrinho['eventos'][$id]);
    } elseif ($tipo === 'sorteio' && isset($carrinho['sorteios'][$id])) {
        unset($carrinho['sorteios'][$id]);
    }

    salvarCarrinho($carrinho);
    header('Location: carrinho.php');
    exit;
}

// Função para carregar itens com segurança
function carregarItensPorIds($pdo, $ids, $tabela, $colunas)
{
    if (empty($ids))
        return [];
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT $colunas FROM $tabela WHERE id IN ($placeholders)");
    $stmt->execute(array_values($ids));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Carregar detalhes e calcular total
$eventos = $sorteios = [];
$total = 0;

if (!empty($_SESSION['carrinho']['eventos'])) {
    $ids = array_keys($_SESSION['carrinho']['eventos']);
    $eventos = carregarItensPorIds($pdo, $ids, 'eventos', 'id, titulo, preco_ingresso');
    foreach ($eventos as $e) {
        $qtd = $_SESSION['carrinho']['eventos'][$e['id']] ?? 1;
        $total += (float) $e['preco_ingresso'] * $qtd;
    }
}

if (!empty($_SESSION['carrinho']['sorteios'])) {
    $ids = array_keys($_SESSION['carrinho']['sorteios']);
    $sorteios = carregarItensPorIds($pdo, $ids, 'sorteios', 'id, titulo, valor_rifa');
    foreach ($sorteios as $s) {
        $qtd = $_SESSION['carrinho']['sorteios'][$s['id']] ?? 1;
        $total += (float) $s['valor_rifa'] * $qtd;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Carrinho - IBDESPERTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>
    <main class="container mt-4">
        <h2>Carrinho de Compras</h2>
        <?php if (empty($eventos) && empty($sorteios)): ?>
            <p class="text-muted">Seu carrinho está vazio.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($eventos as $e):
                    $qtd = $_SESSION['carrinho']['eventos'][$e['id']] ?? 1;
                ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5><?= htmlspecialchars($e['titulo']) ?> (Evento)</h5>
                                <p>Preço unitário: R$ <?= number_format($e['preco_ingresso'], 2, ',', '.') ?></p>

                                <!-- Controle de quantidade -->
                                <form method="POST" class="d-flex align-items-center gap-2 mt-2">
                                    <input type="hidden" name="atualizar_quantidade" value="1">
                                    <input type="hidden" name="tipo" value="evento">
                                    <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">

                                    <button type="submit" name="quantidade" value="<?= max(1, $qtd - 1) ?>"
                                        class="btn btn-sm btn-outline-secondary">–</button>
                                    <span class="fw-bold"><?= $qtd ?></span>
                                    <button type="submit" name="quantidade" value="<?= $qtd + 1 ?>"
                                        class="btn btn-sm btn-outline-secondary">+</button>
                                </form>

                                <a href="?remover=1&tipo=evento&id=<?= (int) $e['id'] ?>"
                                    class="btn btn-sm btn-outline-danger mt-2">Remover</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($sorteios as $s):
                    $qtd = $_SESSION['carrinho']['sorteios'][$s['id']] ?? 1;
                ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5><?= htmlspecialchars($s['titulo']) ?> (Sorteio)</h5>
                                <p>Valor por número: R$ <?= number_format($s['valor_rifa'], 2, ',', '.') ?></p>

                                <!-- Controle de quantidade -->
                                <form method="POST" class="d-flex align-items-center gap-2 mt-2">
                                    <input type="hidden" name="atualizar_quantidade" value="1">
                                    <input type="hidden" name="tipo" value="sorteio">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">

                                    <button type="submit" name="quantidade" value="<?= max(1, $qtd - 1) ?>"
                                        class="btn btn-sm btn-outline-secondary">–</button>
                                    <span class="fw-bold"><?= $qtd ?></span>
                                    <button type="submit" name="quantidade" value="<?= $qtd + 1 ?>"
                                        class="btn btn-sm btn-outline-secondary">+</button>
                                </form>

                                <a href="?remover=1&tipo=sorteio&id=<?= (int) $s['id'] ?>"
                                    class="btn btn-sm btn-outline-danger mt-2">Remover</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h4 class="mt-4">Total: R$ <?= number_format($total, 2, ',', '.') ?></h4>

            <!-- Integração com PayPal -->
            <div id="paypal-button-container" class="mt-3"></div>

            <script src="https://www.paypal.com/sdk/js?client-id=AfHwZdSrW0NJ261v3iKtWHMJg8fznsVv0Zi8E4GZwAi5Iu_87O132OkDCc4Z6WUbieVv0NDGbjajbqoW&currency=BRL"></script>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const total = <?= json_encode(number_format($total, 2, '.', '')) ?>;

                    if (total <= 0) return;

                    paypal.Buttons({
                        createOrder: function(data, actions) {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: {
                                        value: total,
                                        currency_code: 'BRL'
                                    },
                                    description: 'Compra no IBDESPERTAR - Carrinho de Eventos e Sorteios'
                                }]
                            });
                        },
                        onApprove: function(data, actions) {
                            return fetch('processar_pagamento.php', {
                                    method: 'POST',
                                    credentials: 'same-origin', // 👈 ESSENCIAL para enviar cookies/sessão
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        orderID: data.orderID
                                    })
                                })
                                .then(function(res) {
                                    return res.json();
                                })
                                .then(function(details) {
                                    if (details.status === 'success') {
                                        alert('Pagamento realizado com sucesso!');
                                        window.location.href = 'meus_pedidos.php';
                                    } else {
                                        alert('Erro ao processar o pagamento: ' + (details.message || 'Erro desconhecido'));
                                        console.error(details);
                                    }
                                })
                                .catch(function(err) {
                                    console.error('Erro na requisição:', err);
                                    alert('Erro de conexão. Tente novamente.');
                                });
                        },
                        onError: function(err) {
                            console.error('Erro no PayPal:', err);
                            alert('Ocorreu um erro com o pagamento. Tente novamente.');
                        }
                    }).render('#paypal-button-container');
                });
            </script>
            <a href="index.php" class="btn btn-secondary mt-2">Continuar Comprando</a>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>

</html>