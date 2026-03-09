<?php
// Page d'administration basique pour consulter les commandes.
// Attention : ce n'est pas sécurisé (pas d'authentification).
// À utiliser en local ou derrière un accès protégé.

$storageFile = __DIR__ . "/../data/orders.json";

function readOrders($path) {
    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if (!$content) {
        return [];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

$orders = readOrders($storageFile);

function formatDate($value) {
    $d = new DateTime($value);
    return $d->format('d/m/Y H:i');
}

function formatEuro($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Bouqu'art - Commandes</title>
  <link rel="stylesheet" href="../styles.css" />
  <style>
    main { padding: 2.5rem 1.25rem; }
    .admin-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
    .admin-header h1 { margin: 0; }
    .admin-back { margin-top: 0.75rem; display: inline-block; }
    .order { margin-bottom: 1.5rem; }
    .order__meta { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .order__items { margin: 0.5rem 0 0; padding: 0; list-style: none; }
    .order__items li { display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px solid rgba(0,0,0,0.08); }
    .order__items li:last-child { border-bottom: none; }
    .empty { padding: 1.5rem; background: rgba(255,255,255,0.8); border-radius: 1rem; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <div class="brand">
        <h1>Admin Bouqu'art</h1>
        <p>Liste des commandes reçues</p>
      </div>
      <a class="btn btn-secondary" href="../index.html">Retour au site</a>
    </div>
  </header>
  <main class="container">
    <section>
      <div class="admin-header">
        <div>
          <h2>Commandes</h2>
          <p>Nombre de commandes : <strong><?= count($orders) ?></strong></p>
        </div>
      </div>

      <?php if (empty($orders)): ?>
        <div class="empty">
          <p>Aucune commande n'a encore été enregistrée.</p>
          <p>Passez une commande depuis la page principale pour tester.</p>
        </div>
      <?php else: ?>
        <?php foreach (array_reverse($orders) as $order): ?>
          <article class="order">
            <div class="order__meta">
              <div>
                <strong>Commande #<?= htmlspecialchars($order['id']) ?></strong>
                <span><?= htmlspecialchars(formatDate($order['date'])) ?></span>
              </div>
              <div>
                <strong>Total :</strong> <?= htmlspecialchars(formatEuro($order['total'])) ?>
              </div>
            </div>
            <div style="margin-top:0.5rem;">
              <p><strong>Client :</strong> <?= htmlspecialchars($order['customer']['name']) ?> (<?= htmlspecialchars($order['customer']['email']) ?>)</p>
              <p><strong>Adresse :</strong> <?= htmlspecialchars($order['customer']['address']) ?></p>
              <p><strong>Paiement :</strong> <?= htmlspecialchars($order['paymentMethod']) ?></p>
            </div>
            <ul class="order__items">
              <?php foreach ($order['items'] as $item): ?>
                <li>
                  <span><?= htmlspecialchars($item['title']) ?> × <?= (int) $item['qty'] ?></span>
                  <span><?= htmlspecialchars(formatEuro($item['subtotal'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
