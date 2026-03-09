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
    <div class="admin-header">
      <div>
        <h2>Gestion de la boutique</h2>
        <p>Pages : <button id="tabOrders" class="btn btn-secondary">Commandes</button> <button id="tabProducts" class="btn btn-secondary">Produits</button></p>
      </div>
      <a class="btn btn-secondary" href="../shop/">Aller à la boutique</a>
    </div>

    <section id="ordersSection">
      <h3>Commandes</h3>
      <div id="ordersList">
        <p class="empty">Chargement des commandes…</p>
      </div>
    </section>

    <section id="productsSection" hidden>
      <h3>Produits</h3>
      <div class="product-actions" style="margin-bottom: 1rem;">
        <button id="createProduct" class="btn btn-primary">Ajouter un produit</button>
      </div>
      <div id="productsList"></div>

      <div id="productForm" style="display:none; margin-top: 1.25rem; padding: 1rem; border-radius: 1rem; background: rgba(255,255,255,0.88); border: 1px solid rgba(0,0,0,0.1);">
        <h4 id="productFormTitle">Ajouter un produit</h4>
        <form id="formProduct">
          <input type="hidden" name="id" />
          <div style="display:grid; gap:0.75rem;">
            <label>
              Titre
              <input name="title" required />
            </label>
            <label>
              Description
              <textarea name="description" rows="2" required></textarea>
            </label>
            <label>
              Prix (€)
              <input name="price" type="number" step="0.01" required />
            </label>
            <label>
              Stock
              <input name="stock" type="number" step="1" required />
            </label>
            <label>
              Image (URL)
              <input name="image" type="url" required />
            </label>
          </div>
          <div style="margin-top: 1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <button id="cancelProduct" type="button" class="btn btn-secondary">Annuler</button>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script>
    const ordersSection = document.getElementById('ordersSection');
    const productsSection = document.getElementById('productsSection');
    const tabOrders = document.getElementById('tabOrders');
    const tabProducts = document.getElementById('tabProducts');

    const ordersList = document.getElementById('ordersList');
    const productsList = document.getElementById('productsList');
    const productFormContainer = document.getElementById('productForm');
    const productForm = document.getElementById('formProduct');
    const productFormTitle = document.getElementById('productFormTitle');
    const cancelProduct = document.getElementById('cancelProduct');

    function showSection(section) {
      ordersSection.hidden = section !== 'orders';
      productsSection.hidden = section !== 'products';
    }

    tabOrders.addEventListener('click', () => showSection('orders'));
    tabProducts.addEventListener('click', () => showSection('products'));

    async function fetchOrders() {
      try {
        const res = await fetch('/api/orders.php');
        const data = await res.json();
        if (data.ok) return data.orders;
      } catch (e) {}
      return [];
    }

    async function fetchProducts() {
      try {
        const res = await fetch('/api/products.php');
        const data = await res.json();
        if (data.ok) return data.products;
      } catch (e) {}
      return [];
    }

    function formatEuro(amount) {
      return amount.toLocaleString('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
      });
    }

    function formatDate(value) {
      const d = new Date(value);
      return d.toLocaleString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      });
    }

    function renderOrders(orders) {
      if (!orders.length) {
        ordersList.innerHTML = '<div class="empty"><p>Aucune commande pour le moment.</p></div>';
        return;
      }

      ordersList.innerHTML = orders
        .slice()
        .reverse()
        .map((order) => {
          const itemsHtml = order.items
            .map((item) =>
              `<li><span>${item.title} × ${item.qty}</span><span>${formatEuro(item.subtotal)}</span></li>`
            )
            .join('');

          return `
            <article class="order">
              <div class="order__meta">
                <div>
                  <strong>Commande #${order.id}</strong>
                  <span>${formatDate(order.date)}</span>
                </div>
                <div><strong>Total :</strong> ${formatEuro(order.total)}</div>
              </div>
              <div style="margin-top:0.5rem;">
                <p><strong>Client :</strong> ${order.customer.name} (${order.customer.email})</p>
                <p><strong>Adresse :</strong> ${order.customer.address}</p>
                <p><strong>Paiement :</strong> ${order.paymentMethod}</p>
              </div>
              <ul class="order__items">${itemsHtml}</ul>
            </article>
          `;
        })
        .join('');
    }

    function renderProducts(products) {
      if (!products.length) {
        productsList.innerHTML = '<div class="empty"><p>Aucun produit.</p></div>';
        return;
      }

      productsList.innerHTML = products
        .map((product) => {
          return `
            <div class="order" style="padding:1rem;">
              <div class="order__meta">
                <div>
                  <strong>${product.title}</strong>
                  <span>${product.description}</span>
                </div>
                <div>
                  <strong>${formatEuro(product.price)}</strong>
                  <span>Stock : ${product.stock ?? 0}</span>
                </div>
              </div>
              <div style="margin-top:0.75rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                <button class="btn btn-primary" data-action="edit" data-id="${product.id}">Modifier</button>
                <button class="btn btn-secondary" data-action="delete" data-id="${product.id}">Supprimer</button>
              </div>
            </div>
          `;
        })
        .join('');
    }

    async function loadOrders() {
      const orders = await fetchOrders();
      renderOrders(orders);
    }

    async function loadProducts() {
      const products = await fetchProducts();
      renderProducts(products);
    }

    function openProductForm(product = null) {
      productFormContainer.style.display = 'block';
      productFormTitle.textContent = product ? 'Modifier un produit' : 'Ajouter un produit';

      productForm.id.value = product?.id ?? '';
      productForm.title.value = product?.title ?? '';
      productForm.description.value = product?.description ?? '';
      productForm.price.value = product?.price ?? '';
      productForm.stock.value = product?.stock ?? '';
      productForm.image.value = product?.image ?? '';
    }

    function closeProductForm() {
      productFormContainer.style.display = 'none';
      productForm.reset();
    }

    async function createProduct(data) {
      await fetch('/api/products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      await loadProducts();
    }

    async function updateProduct(data) {
      await fetch('/api/products.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      await loadProducts();
    }

    async function deleteProduct(id) {
      if (!confirm('Supprimer ce produit ?')) return;
      await fetch('/api/products.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });
      await loadProducts();
    }

    document.getElementById('createProduct').addEventListener('click', () => openProductForm());

    productsList.addEventListener('click', (event) => {
      const button = event.target.closest('button');
      if (!button) return;
      const action = button.dataset.action;
      const id = button.dataset.id;
      if (action === 'edit') {
        fetch('/api/products.php')
          .then((r) => r.json())
          .then((data) => data.products || [])
          .then((products) => products.find((p) => p.id === id))
          .then((product) => {
            if (product) openProductForm(product);
          });
      }
      if (action === 'delete') {
        deleteProduct(id);
      }
    });

    productForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(productForm);
      const payload = {
        id: formData.get('id') || undefined,
        title: formData.get('title'),
        description: formData.get('description'),
        price: Number(formData.get('price')) || 0,
        stock: Number(formData.get('stock')) || 0,
        image: formData.get('image'),
      };

      if (payload.id) {
        await updateProduct(payload);
      } else {
        await createProduct(payload);
      }

      closeProductForm();
    });

    cancelProduct.addEventListener('click', () => closeProductForm());

    // Chargement initial
    loadOrders();
    loadProducts();
  </script>
</body>
</html>
