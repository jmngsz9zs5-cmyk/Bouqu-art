let PRODUCTS = [];

const STORAGE_KEY = "bouquart-cart-v1";
const ORDERS_KEY = "bouquart-orders-v1";

async function fetchProducts() {
  try {
    const response = await fetch("/api/products.php");
    if (!response.ok) return [];
    const json = await response.json();
    if (!json.ok) return [];
    return json.products;
  } catch {
    return [];
  }
}

function formatEuro(amount) {
  return amount.toLocaleString("fr-FR", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: 2,
  });
}

function formatDate(date) {
  return new Date(date).toLocaleString("fr-FR", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function loadCart() {
  const stored = window.localStorage.getItem(STORAGE_KEY);
  if (!stored) return {};

  try {
    return JSON.parse(stored);
  } catch {
    return {};
  }
}

function saveCart(cart) {
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
  updateCartCount(cart);
}

function updateCartCount(cart) {
  const count = Object.values(cart).reduce((sum, qty) => sum + qty, 0);
  document.getElementById("cartCount").textContent = count;
}

function getCartTotal(cart) {
  return Object.entries(cart).reduce((total, [id, qty]) => {
    const product = PRODUCTS.find((p) => p.id === id);
    if (!product) return total;
    return total + product.price * qty;
  }, 0);
}

function getProductStock(id) {
  const product = PRODUCTS.find((p) => p.id === id);
  return product ? (product.stock ?? 0) : 0;
}

function getRemainingStock(id, cart) {
  return Math.max(0, getProductStock(id) - (cart[id] || 0));
}

function validateEmail(email) {
  return /^\S+@\S+\.\S+$/.test(email);
}

function loadOrders() {
  const stored = window.localStorage.getItem(ORDERS_KEY);
  if (!stored) return [];

  try {
    return JSON.parse(stored);
  } catch {
    return [];
  }
}

function saveOrders(orders) {
  window.localStorage.setItem(ORDERS_KEY, JSON.stringify(orders));
}

function addOrder(order) {
  const orders = loadOrders();
  orders.unshift(order);
  saveOrders(orders);
}

function clearCheckoutForm() {
  const fields = [
    "customerName",
    "customerEmail",
    "customerAddress",
    "paymentMethod",
  ];

  fields.forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;

    if (el.tagName === "SELECT") {
      el.value = "";
    } else {
      el.value = "";
    }
  });
}

async function fetchOrders() {
  try {
    const response = await fetch("/api/orders.php");
    if (!response.ok) return null;
    const data = await response.json();
    if (!data.ok) return null;
    return data.orders;
  } catch {
    return null;
  }
}

async function renderOrdersModal() {
  const list = document.getElementById("ordersList");
  const stored = loadOrders();

  // Affiche d'abord les commandes locale (en mode démo)
  let orders = stored;

  // Si le backend répond, on le charge et on écrase l'affichage local
  const remote = await fetchOrders();
  if (remote) {
    orders = remote;
    saveOrders(remote);
  }

  if (orders.length === 0) {
    list.innerHTML = `<p>Vous n'avez pas encore passé de commande.</p>`;
    return;
  }

  list.innerHTML = "";

  orders.forEach((order) => {
    const container = document.createElement("div");
    container.className = "order";

    container.innerHTML = `
      <div class="order__header">
        <strong>Commande #${order.id}</strong>
        <span>${formatDate(order.date)}</span>
      </div>
      <div><strong>Client:</strong> ${order.customer.name} — ${order.customer.email}</div>
      <div><strong>Adresse:</strong> ${order.customer.address}</div>
      <div><strong>Paiement:</strong> ${order.paymentMethod}</div>
      <ul class="order__items">
        ${order.items
          .map(
            (item) =>
              `<li><span>${item.title} × ${item.qty}</span><span>${formatEuro(
                item.subtotal
              )}</span></li>`
          )
          .join("")}
      </ul>
      <div class="order__total">
        <span>Total</span>
        <span>${formatEuro(order.total)}</span>
      </div>
    `;

    list.appendChild(container);
  });
}

function renderProducts(cart) {
  const container = document.getElementById("products");
  container.innerHTML = "";

  PRODUCTS.forEach((product) => {
    const card = document.createElement("article");
    card.className = "product";

    const remaining = getRemainingStock(product.id, cart);
    const isSoldOut = remaining <= 0;

    card.innerHTML = `
      <img class="product__image" src="${product.image}" alt="${product.title}" />
      <div class="product__body">
        <h3 class="product__title">${product.title}</h3>
        <p class="product__desc">${product.description}</p>
        <div class="product__meta">
          <div class="product__price">${formatEuro(product.price)}</div>
          <div class="product__stock">Stock : ${remaining}</div>
        </div>
        <div class="product__footer">
          <button class="btn btn-primary product__add" data-product="${product.id}" ${
      isSoldOut ? "disabled" : ""
    }>${isSoldOut ? "Rupture" : "Ajouter"}</button>
        </div>
      </div>
    `;

    container.appendChild(card);
  });
}

function renderCartModal(cart) {
  const itemsContainer = document.getElementById("cartItems");
  itemsContainer.innerHTML = "";

  const entries = Object.entries(cart);
  if (entries.length === 0) {
    itemsContainer.innerHTML = `<p>Votre panier est vide. Ajoutez un bouquet pour commencer.</p>`;
  } else {
    entries.forEach(([id, qty]) => {
      const product = PRODUCTS.find((p) => p.id === id);
      if (!product) return;

      const item = document.createElement("div");
      item.className = "cart-item";

      item.innerHTML = `
        <div class="cart-item__details">
          <p class="cart-item__title">${product.title}</p>
          <div class="cart-item__meta">
            <span>${formatEuro(product.price)} x ${qty}</span>
            <span><strong>${formatEuro(product.price * qty)}</strong></span>
          </div>
        </div>
        <div class="cart-item__qty">
          <button class="qty-decrease" data-product="${product.id}" aria-label="Réduire">–</button>
          <span>${qty}</span>
          <button class="qty-increase" data-product="${product.id}" aria-label="Augmenter">+</button>
        </div>
        <button class="cart-item__remove" data-product="${product.id}" aria-label="Supprimer">✕</button>
      `;

      itemsContainer.appendChild(item);
    });
  }

  const total = getCartTotal(cart);
  document.querySelector(".cart-total").textContent = formatEuro(total);
}

function openCart() {
  const modal = document.getElementById("cartModal");
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function openOrders() {
  const modal = document.getElementById("ordersModal");
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function closeAllModals() {
  document.querySelectorAll(".modal.open").forEach((modal) => {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
  });
}

function showToast(message) {
  const notification = document.createElement("div");
  notification.style.cssText = `
    position: fixed;
    bottom: 1.25rem;
    right: 1.25rem;
    padding: 0.75rem 1rem;
    border-radius: 999px;
    background: rgba(34, 34, 34, 0.88);
    color: white;
    box-shadow: 0 14px 30px rgba(0,0,0,0.2);
    z-index: 60;
    font-weight: 600;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 220ms ease, transform 220ms ease;
  `;
  notification.textContent = message;
  document.body.appendChild(notification);

  requestAnimationFrame(() => {
    notification.style.opacity = "1";
    notification.style.transform = "translateY(0)";
  });

  setTimeout(() => {
    notification.style.opacity = "0";
    notification.style.transform = "translateY(12px)";
    notification.addEventListener(
      "transitionend",
      () => {
        notification.remove();
      },
      { once: true }
    );
  }, 2200);
}

async function init() {
  const cart = loadCart();
  updateCartCount(cart);

  PRODUCTS = await fetchProducts();
  renderProducts(cart);

  document.getElementById("year").textContent = new Date().getFullYear();

  // Charge les commandes existantes en local + backend
  renderOrdersModal();

  document.getElementById("products").addEventListener("click", (event) => {
    const button = event.target.closest("button[data-product]");
    if (!button) return;

    const productId = button.dataset.product;
    const current = loadCart();
    const remaining = getRemainingStock(productId, current);

    if (remaining <= 0) {
      showToast("Stock insuffisant pour ce produit.");
      return;
    }

    current[productId] = (current[productId] ?? 0) + 1;
    saveCart(current);

    renderProducts(current);
    showToast("Ajouté au panier !");
  });

  const cartButton = document.getElementById("cartButton");
  cartButton.addEventListener("click", () => {
    const current = loadCart();
    renderCartModal(current);
    openCart();
  });

  const ordersButton = document.getElementById("ordersButton");
  ordersButton.addEventListener("click", () => {
    renderOrdersModal();
    openOrders();
  });

  const closeButtons = document.querySelectorAll("[data-close]");
  closeButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const modal = btn.closest(".modal");
      if (modal) {
        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");
      }
    });
  });

  document.getElementById("cartItems").addEventListener("click", (event) => {
    const productId = event.target.closest("[data-product]")?.dataset.product;
    if (!productId) return;

    const cartState = loadCart();

    if (event.target.matches(".qty-increase")) {
      const remaining = getRemainingStock(productId, cartState);
      if (remaining <= 0) {
        showToast("Stock insuffisant pour augmenter.");
        return;
      }

      cartState[productId] = (cartState[productId] ?? 0) + 1;
      saveCart(cartState);
      renderCartModal(cartState);
      renderProducts(cartState);
      return;
    }

    if (event.target.matches(".qty-decrease")) {
      const next = Math.max(0, (cartState[productId] ?? 0) - 1);
      if (next <= 0) {
        delete cartState[productId];
      } else {
        cartState[productId] = next;
      }
      saveCart(cartState);
      renderCartModal(cartState);
      renderProducts(cartState);
      return;
    }

    if (event.target.matches(".cart-item__remove")) {
      delete cartState[productId];
      saveCart(cartState);
      renderCartModal(cartState);
      renderProducts(cartState);
      return;
    }
  });

  document.getElementById("clearCart").addEventListener("click", () => {
    saveCart({});
    renderCartModal({});
    renderProducts({});
  });

  document.getElementById("checkout").addEventListener("click", async () => {
    const cartState = loadCart();
    const total = getCartTotal(cartState);
    if (total <= 0) {
      showToast("Votre panier est vide.");
      return;
    }

    const name = document.getElementById("customerName").value.trim();
    const email = document.getElementById("customerEmail").value.trim();
    const address = document.getElementById("customerAddress").value.trim();
    const payment = document.getElementById("paymentMethod").value;

    if (!name) {
      showToast("Merci de renseigner votre nom.");
      document.getElementById("customerName").focus();
      return;
    }

    if (!email || !validateEmail(email)) {
      showToast("Merci de renseigner une adresse email valide.");
      document.getElementById("customerEmail").focus();
      return;
    }

    if (!address) {
      showToast("Merci de renseigner une adresse de livraison.");
      document.getElementById("customerAddress").focus();
      return;
    }

    if (!payment) {
      showToast("Merci de sélectionner un mode de paiement.");
      document.getElementById("paymentMethod").focus();
      return;
    }

    const order = {
      id: Math.floor(Math.random() * 900000) + 100000,
      date: new Date().toISOString(),
      total,
      paymentMethod: payment,
      customer: {
        name,
        email,
        address,
      },
      items: Object.entries(cartState).map(([id, qty]) => {
        const product = PRODUCTS.find((p) => p.id === id);
        return {
          id,
          title: product?.title ?? id,
          qty,
          subtotal: product ? product.price * qty : 0,
        };
      }),
    };

    const sendToServer = async () => {
      try {
        const response = await fetch("/api/orders.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(order),
        });

        const result = await response.json();
        if (result.ok) {
          showToast(
            `Merci ${name} ! Votre commande de ${formatEuro(total)} a été envoyée.`
          );
          return true;
        }

        console.warn("Échec envoi commande", result);
        return false;
      } catch (error) {
        console.warn("Erreur envoi commande", error);
        return false;
      }
    };

    const sent = await sendToServer();

    if (!sent) {
      showToast(
        "Commande enregistrée en local (serveur indisponible). Réessayez plus tard."
      );
    }

    addOrder(order);

    saveCart({});
    clearCheckoutForm();
    renderCartModal({});

    PRODUCTS = await fetchProducts();
    renderProducts({});
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAllModals();
    }
  });
}

init();
