# Bouqu-art

Site vitrine et catalogue pour une boutique de bouquets en satin.

## 🧩 Structure du projet

- `/shop/index.html` : boutique en ligne (achat de bouquets).
- `/admin/index.php` : interface de gestion (produits, commandes).
- `styles.css` : styles de l'interface.
- `script.js` : logique du panier, commandes et historique (stockées en `localStorage`) et interactions.

## ▶️ Lancer localement

### Option 1 — Sans backend (démonstration)
Ouvrez `index.html` dans un navigateur moderne (Chrome, Firefox, Edge).

> Note : dans ce mode, les commandes sont stockées uniquement localement (dans le navigateur).

### Option 2 — Avec backend PHP (pour que le vendeur voie les commandes)
1. Assurez-vous d’avoir PHP installé (PHP 7.4+).
2. Lancez le serveur PHP depuis le dossier du projet :
   ```bash
   php -S localhost:8000
   ```
3. Ouvrez `http://localhost:8000` dans votre navigateur.

Le backend stocke les commandes dans `data/orders.json` et les produits dans `data/products.json`.

Une fois le serveur démarré, accédez à :

- Boutique client : `http://localhost:8000/shop/`
- Espace vendeur (gestion des produits + commandes) : `http://localhost:8000/admin/`
