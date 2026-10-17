<?php
declare(strict_types=1);

session_start();

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah(int|float $value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

function productImage(array $product): ?string
{
    $images = [
        'ASUS Vivobook 14' => 'assets/products/asus-vivobook-14.jpg',
        'Lenovo LOQ 15' => 'assets/products/lenovo-loq-15.jpg',
        'MacBook Air 13 M4' => 'assets/products/macbook-air-13-m4.jpg',
        'Samsung Galaxy A56 5G' => null,
        'Xiaomi Redmi Note 14 Pro' => 'assets/products/xiaomi-redmi-note-14-pro.jpg',
        'iPhone 16 128GB' => 'assets/products/iphone-16-128gb.jpg',
        'PC Creator Ryzen 7' => null,
        'Monitor LG UltraGear 27"' => 'assets/products/monitor-lg-ultragear-27.jpg',
        'Monitor Xiaomi A27i' => 'assets/products/monitor-xiaomi-a27i.png',
        'Headset Logitech G Pro X' => null,
        'Sony WH-1000XM5' => null,
        'Keyboard Keychron K2' => 'assets/products/keyboard-keychron-k2.jpg',
    ];

    return $images[(string) ($product['name'] ?? '')] ?? null;
}

function go(string $page = 'home'): never
{
    header('Location: ?page=' . rawurlencode($page));
    exit;
}

function message(string $text, string $type = 'success'): void
{
    $_SESSION['message'] = ['text' => $text, 'type' => $type];
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function adminOnly(): void
{
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak. Halaman ini khusus admin.');
    }
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'electra_store';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(140) NOT NULL,
        category VARCHAR(40) NOT NULL,
        description VARCHAR(255) NOT NULL DEFAULT '',
        price BIGINT UNSIGNED NOT NULL,
        stock INT UNSIGNED NOT NULL DEFAULT 0,
        icon VARCHAR(12) NOT NULL DEFAULT '📦',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        total BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT orders_user_fk FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        product_name VARCHAR(140) NOT NULL,
        quantity INT UNSIGNED NOT NULL,
        unit_price BIGINT UNSIGNED NOT NULL,
        CONSTRAINT items_order_fk FOREIGN KEY (order_id) REFERENCES orders(id),
        CONSTRAINT items_product_fk FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB");

    $categories = ['Laptop', 'Smartphone', 'PC & Komponen', 'Monitor', 'Audio', 'Aksesori'];
    if ((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
        $seed = [
            ['ASUS Vivobook 14', 'Laptop', 'Laptop tipis untuk kerja dan kuliah, Ryzen 5, RAM 16GB.', 8999000, 12, '💻'],
            ['Lenovo LOQ 15', 'Laptop', 'Laptop gaming GeForce RTX 4050 dengan layar 144Hz.', 16499000, 7, '🎮'],
            ['MacBook Air 13 M4', 'Laptop', 'Laptop ringan dengan chip Apple M4 dan baterai tahan lama.', 19999000, 5, '🖥️'],
            ['Samsung Galaxy A56 5G', 'Smartphone', 'Layar AMOLED 120Hz, kamera 50MP, dan koneksi 5G.', 5999000, 18, '📱'],
            ['Xiaomi Redmi Note 14 Pro', 'Smartphone', 'Kamera 200MP dan layar AMOLED 1.5K.', 4299000, 23, '📲'],
            ['iPhone 16 128GB', 'Smartphone', 'Kamera canggih, performa A18, dan USB-C.', 13999000, 8, '🍏'],
            ['PC Creator Ryzen 7', 'PC & Komponen', 'PC rakitan 8-core dengan RAM 32GB dan SSD 1TB.', 18999000, 4, '🖥️'],
            ['Monitor LG UltraGear 27"', 'Monitor', 'QHD IPS 180Hz, cocok untuk gaming dan kreasi konten.', 4999000, 9, '🪟'],
            ['Monitor Xiaomi A27i', 'Monitor', 'Monitor IPS Full HD 100Hz dengan desain minimalis.', 1599000, 15, '🖥️'],
            ['Headset Logitech G Pro X', 'Audio', 'Headset gaming berkabel dengan mikrofon jernih.', 1899000, 11, '🎧'],
            ['Sony WH-1000XM5', 'Audio', 'Headphone wireless premium dengan noise cancelling.', 4999000, 6, '🎵'],
            ['Keyboard Keychron K2', 'Aksesori', 'Keyboard mekanikal wireless layout ringkas.', 1399000, 14, '⌨️'],
        ];
        $insert = $pdo->prepare('INSERT INTO products (name, category, description, price, stock, icon) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($seed as $item) {
            $insert->execute($item);
        }
    }
    $adminEmail = 'admin@electra.local';
    $checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $checkAdmin->execute([$adminEmail]);
    if (!$checkAdmin->fetch()) {
        $insertAdmin = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $insertAdmin->execute(['Admin Electra', $adminEmail, password_hash('Admin123!', PASSWORD_DEFAULT), 'admin']);
    }
} catch (PDOException $exception) {
    error_log('Electra database connection/setup failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('<!doctype html><html lang="id"><meta charset="utf-8"><title>Database belum siap</title><body style="font:16px Arial,sans-serif;max-width:680px;margin:12vh auto;padding:24px;color:#17243a"><h1>Electra belum bisa terhubung ke database</h1><p>Pastikan MySQL Laragon aktif dan konfigurasi <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, serta <code>DB_PASS</code> sudah benar.</p><p>Jika masalah berlanjut, periksa log error PHP.</p></body></html>');
}

$page = (string) ($_GET['page'] ?? 'home');
$allCategories = ['Semua', ...$categories];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.');
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            message('Lengkapi nama dan email yang valid. Kata sandi minimal 8 karakter.', 'error');
            go('register');
        }
        try {
            $statement = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'customer'];
            message('Akun berhasil dibuat. Selamat datang di Electra!');
            go();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                message('Email tersebut sudah terdaftar. Silakan masuk.', 'error');
                go('register');
            }
            throw $exception;
        }
    }
    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $accountType = (string) ($_POST['account_type'] ?? 'customer');
        if (!in_array($accountType, ['customer', 'admin'], true)) {
            $accountType = 'customer';
        }
        $statement = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $statement->execute([$email]);
        $user = $statement->fetch();
        $passwordValid = $user && password_verify((string) ($_POST['password'] ?? ''), $user['password']);
        if (!$user || $user['role'] !== $accountType || !$passwordValid) {
            $_SESSION['login_account_type'] = $accountType;
            message(
                $accountType === 'admin'
                    ? 'Login akun admin gagal. Periksa kembali email dan kata sandi admin.'
                    : 'Login akun pelanggan gagal. Periksa kembali email dan kata sandi Anda.',
                'error'
            );
            go('login');
        }
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
        message('Berhasil masuk. Hai, ' . $user['name'] . '!');
        go($user['role'] === 'admin' ? 'admin' : 'home');
    }
    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        message('Anda sudah keluar.');
        go();
    }
    if ($action === 'add_to_cart') {
        $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
        $quantity = max(1, min(10, (int) ($_POST['quantity'] ?? 1)));
        $statement = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ?');
        $statement->execute([$id]);
        $product = $statement->fetch();
        $current = (int) ($_SESSION['cart'][$id] ?? 0);
        $maxQuantity = $product ? min(10, (int) $product['stock']) : 0;
        if (!$product || $current + $quantity > $maxQuantity) {
            message('Stok tidak mencukupi atau batas 10 unit per produk sudah tercapai.', 'error');
        } else {
            $_SESSION['cart'][$id] = $current + $quantity;
            message($product['name'] . ' ditambahkan ke keranjang.');
        }
        if (($_POST['return_to'] ?? '') === 'product') {
            header('Location: ?page=product&id=' . $id);
            exit;
        }
        go();
    }
    if ($action === 'update_cart') {
        $adjusted = false;
        $findProduct = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
        foreach (($_SESSION['cart'] ?? []) as $id => $quantity) {
            $rawQuantity = $_POST['quantity'][$id] ?? $quantity;
            if (!is_scalar($rawQuantity)) {
                $adjusted = true;
                continue;
            }
            $requested = filter_var($rawQuantity, FILTER_VALIDATE_INT);
            if ($requested === false || $requested < 0) {
                $adjusted = true;
                continue;
            }
            if ($requested === 0) {
                unset($_SESSION['cart'][$id]);
                continue;
            }
            $findProduct->execute([(int) $id]);
            $product = $findProduct->fetch();
            if (!$product) {
                unset($_SESSION['cart'][$id]);
                $adjusted = true;
                continue;
            }
            $next = min((int) $requested, 10, (int) $product['stock']);
            $adjusted = $adjusted || $next !== (int) $requested;
            if ($next === 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id] = $next;
            }
        }
        message($adjusted
            ? 'Keranjang diperbarui. Jumlah disesuaikan dengan stok tersedia atau ada item yang perlu diperiksa.'
            : 'Keranjang berhasil diperbarui.');
        go('cart');
    }
    if ($action === 'checkout') {
        if (empty($_SESSION['user'])) {
            message('Masuk terlebih dahulu untuk menyelesaikan pesanan.', 'error');
            go('login');
        }
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            message('Keranjang Anda masih kosong.', 'error');
            go('cart');
        }
        ksort($cart, SORT_NUMERIC);
        try {
            $pdo->beginTransaction();
            $items = [];
            $total = 0;
            $lock = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE');
            foreach ($cart as $id => $quantity) {
                $lock->execute([(int) $id]);
                $product = $lock->fetch();
                if (!$product || (int) $quantity < 1 || (int) $quantity > 10 || (int) $product['stock'] < (int) $quantity) {
                    throw new DomainException('Stok salah satu produk berubah. Periksa kembali keranjang Anda.');
                }
                $items[] = [$product, (int) $quantity];
                $total += (int) $product['price'] * (int) $quantity;
            }
            $order = $pdo->prepare('INSERT INTO orders (user_id, total) VALUES (?, ?)');
            $order->execute([(int) $_SESSION['user']['id'], $total]);
            $orderId = (int) $pdo->lastInsertId();
            $addItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)');
            $reduce = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($items as [$product, $quantity]) {
                $addItem->execute([$orderId, $product['id'], $product['name'], $quantity, $product['price']]);
                $reduce->execute([$quantity, $product['id']]);
            }
            $pdo->commit();
            unset($_SESSION['cart']);
            message('Pesanan #' . $orderId . ' berhasil dibuat. Terima kasih sudah berbelanja!');
            go('orders');
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            message($exception->getMessage(), 'error');
            go('cart');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
    if ($action === 'save_product') {
        adminOnly();
        $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_INT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $icon = trim((string) ($_POST['icon'] ?? '📦'));
        if ($name === '' || mb_strlen($name) > 140 || !in_array($category, $categories, true) ||
            $description === '' || mb_strlen($description) > 255 || !$price || $price < 1 ||
            $stock === false || $stock === null || $stock < 0 || mb_strlen($icon) > 12) {
            message('Periksa kembali nama, kategori, deskripsi, harga, stok, dan ikon produk.', 'error');
            go('admin');
        }
        if ($id) {
            $exists = $pdo->prepare('SELECT id FROM products WHERE id = ?');
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                message('Produk yang ingin diperbarui tidak ditemukan.', 'error');
                go('admin');
            }
            $statement = $pdo->prepare('UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?, icon = ? WHERE id = ?');
            $statement->execute([$name, $category, $description, $price, $stock, $icon ?: '📦', $id]);
            message('Produk berhasil diperbarui.');
        } else {
            $statement = $pdo->prepare('INSERT INTO products (name, category, description, price, stock, icon) VALUES (?, ?, ?, ?, ?, ?)');
            $statement->execute([$name, $category, $description, $price, $stock, $icon ?: '📦']);
            message('Produk baru berhasil ditambahkan.');
        }
        go('admin');
    }
}

$user = $_SESSION['user'] ?? null;
$cartCount = array_sum($_SESSION['cart'] ?? []);
$notice = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
$loginAccountType = $_SESSION['login_account_type'] ?? 'customer';
unset($_SESSION['login_account_type']);
$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$stockCount = (int) $pdo->query('SELECT COALESCE(SUM(stock), 0) FROM products')->fetchColumn();
$orderCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

function productCard(array $product): void
{
    $empty = (int) $product['stock'] === 0;
    $detailUrl = '?page=product&id=' . (int) $product['id'];
    $image = productImage($product);
    ?>
    <article class="product-card">
        <a class="product-art tone-<?= (int) $product['id'] % 5 ?>" href="<?= e($detailUrl) ?>" aria-label="Lihat detail <?= e($product['name']) ?>">
            <span class="product-tag"><?= e($product['category']) ?></span>
            <?php if ($image): ?><img class="product-image" src="<?= e($image) ?>" alt="<?= e($product['name']) ?>" loading="lazy"><?php else: ?><span class="product-emoji" aria-hidden="true"><?= e($product['icon']) ?></span><?php endif; ?><span class="art-orbit"></span>
        </a>
        <div class="product-copy">
            <div class="stock-line"><span class="stock-dot <?= $empty ? 'is-empty' : '' ?>"></span><?= $empty ? 'Stok habis' : 'Stok ' . number_format((int) $product['stock']) . ' unit' ?></div>
            <h3><a href="<?= e($detailUrl) ?>"><?= e($product['name']) ?></a></h3><p><?= e($product['description']) ?></p>
            <div class="product-buy"><strong><?= rupiah((int) $product['price']) ?></strong>
                <form method="post"><?= csrf() ?><input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><button class="add-button" type="submit" <?= $empty ? 'disabled' : '' ?> aria-label="Tambah <?= e($product['name']) ?> ke keranjang">+</button></form>
            </div>
            <a class="product-detail-link" href="<?= e($detailUrl) ?>">Lihat detail <span>↗</span></a>
        </div>
    </article>
    <?php
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#f3f5f8">
    <meta name="description" content="Electra, toko teknologi pilihan untuk laptop, smartphone, audio, dan aksesori.">
    <title>Electra — Teknologi yang bikin hidup lebih mudah</title><link rel="icon" type="image/svg+xml" href="logo-electra.svg"><link rel="stylesheet" href="style.css">
</head>
<body>
<div class="announcement"><span>✦</span> Tech picks 2026 — temukan gear yang pas buat kamu <a href="?page=home#produk">Belanja sekarang <b>↗</b></a></div>
<header class="site-header">
    <a class="brand" href="?page=home"><img class="brand-mark" src="logo-electra.svg" alt=""> electra<span class="brand-period">.</span></a>
    <nav class="main-nav" aria-label="Navigasi utama"><a href="?page=home#produk">Produk</a><a href="?page=home#produk">Kategori</a><a href="?page=shipping">Pengiriman</a><?php if ($user && ($user['role'] ?? '') === 'customer'): ?><a href="?page=dashboard">Dashboard</a><?php endif; ?><?php if ($user): ?><a href="?page=orders">Pesanan</a><?php endif; ?><?php if (($user['role'] ?? '') === 'admin'): ?><a href="?page=admin">Admin</a><?php endif; ?></nav>
    <div class="header-actions"><a class="cart-link" href="?page=cart" aria-label="Keranjang">Keranjang <span><?= (int) $cartCount ?></span></a>
        <?php if ($user): ?><span class="user-greeting">Hai, <?= e(explode(' ', $user['name'])[0]) ?></span><form method="post" class="logout-form"><?= csrf() ?><input type="hidden" name="action" value="logout"><button class="login-link" type="submit">Keluar</button></form>
        <?php else: ?><a class="login-link" href="?page=login">Masuk</a><a class="signup-link" href="?page=register">Daftar <span>↗</span></a><?php endif; ?>
    </div>
</header>
<main>
<?php if ($notice): ?><div class="notice notice-<?= e($notice['type']) ?>" role="status"><?= e($notice['text']) ?></div><?php endif; ?>

<?php if ($page === 'login' || $page === 'register'): ?>
    <section class="auth-layout"><div class="auth-aside"><span class="eyebrow">GOOD TECH. GOOD DAYS.</span><h1>Upgrade harimu,<br><em>mulai dari sini.</em></h1><p>Temukan perangkat yang bikin kerja, main, dan hidup terasa lebih seru.</p><div class="auth-orbit">✦</div></div>
        <div class="auth-card"><span class="eyebrow"><?= $page === 'login' ? 'SENANG MELIHATMU LAGI' : 'GABUNG KOMUNITAS ELECTRA' ?></span><h2><?= $page === 'login' ? 'Masuk ke akunmu' : 'Buat akun baru' ?></h2><p class="muted"><?= $page === 'login' ? 'Lanjutkan cari gear favoritmu.' : 'Daftar gratis dan mulai eksplorasi.' ?></p>
            <form method="post" class="stack-form"><?= csrf() ?><input type="hidden" name="action" value="<?= $page === 'login' ? 'login' : 'register' ?>">
                <?php if ($page === 'register'): ?><label>Nama lengkap<input name="name" type="text" maxlength="100" autocomplete="name" required placeholder="Nama kamu"></label><?php endif; ?>
                <?php if ($page === 'login'): ?><label>Masuk sebagai<select name="account_type" required><option value="customer" <?= $loginAccountType === 'customer' ? 'selected' : '' ?>>User / Pelanggan</option><option value="admin" <?= $loginAccountType === 'admin' ? 'selected' : '' ?>>Admin</option></select></label><?php endif; ?>
                <label>Email<input name="email" type="email" maxlength="190" autocomplete="email" required placeholder="nama@email.com"></label><label>Kata sandi<input name="password" type="password" minlength="8" autocomplete="<?= $page === 'login' ? 'current-password' : 'new-password' ?>" required placeholder="Minimal 8 karakter"></label>
                <button class="button button-primary button-full" type="submit"><?= $page === 'login' ? 'Masuk ke Electra' : 'Buat akun' ?> <span>↗</span></button>
            </form><p class="auth-switch"><?= $page === 'login' ? 'Belum punya akun?' : 'Sudah punya akun?' ?> <a href="?page=<?= $page === 'login' ? 'register' : 'login' ?>"><?= $page === 'login' ? 'Daftar sekarang' : 'Masuk di sini' ?></a></p>
        </div>
    </section>

<?php elseif ($page === 'cart'): ?>
    <?php $cartProducts = []; $cartTotal = 0;
    foreach ($_SESSION['cart'] ?? [] as $id => $quantity) {
        $statement = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $statement->execute([(int) $id]);
        if ($product = $statement->fetch()) {
            $product['quantity'] = (int) $quantity;
            $product['subtotal'] = (int) $product['price'] * (int) $quantity;
            $cartTotal += $product['subtotal'];
            $cartProducts[] = $product;
        }
    } ?>
    <section class="simple-page"><div class="section-heading"><div><span class="eyebrow">TINGGAL SELANGKAH LAGI</span><h1>Keranjang <em>belanja.</em></h1></div><a class="text-link" href="?page=home#produk">← Lanjut belanja</a></div>
        <?php if (!$cartProducts): ?><div class="empty-state"><span>🛍️</span><h2>Keranjangmu masih kosong</h2><p>Yuk, temukan teknologi favoritmu hari ini.</p><a class="button button-primary" href="?page=home#produk">Jelajahi produk ↗</a></div>
        <?php else: ?><div class="cart-layout"><form method="post" class="cart-items"><?= csrf() ?><input type="hidden" name="action" value="update_cart">
            <?php foreach ($cartProducts as $product): ?><article class="cart-item"><div class="cart-icon"><?= e($product['icon']) ?></div><div class="cart-product"><span class="eyebrow"><?= e($product['category']) ?></span><h3><?= e($product['name']) ?></h3><span class="muted"><?= rupiah((int) $product['price']) ?></span><span class="muted">Stok tersedia: <?= (int) $product['stock'] ?> unit</span></div><label class="quantity-label">Jumlah<input type="number" name="quantity[<?= (int) $product['id'] ?>]" value="<?= (int) $product['quantity'] ?>" min="0" max="<?= min(10, (int) $product['stock']) ?>"></label><strong class="cart-subtotal"><?= rupiah($product['subtotal']) ?></strong></article><?php endforeach; ?>
            <button class="button button-secondary" type="submit">Perbarui keranjang</button></form>
            <aside class="summary-card"><span class="eyebrow">RINGKASAN PESANAN</span><div class="summary-row"><span>Subtotal</span><strong><?= rupiah($cartTotal) ?></strong></div><div class="summary-row"><span>Pengiriman</span><strong>Belum dihitung</strong></div><div class="summary-total"><span>Total produk</span><strong><?= rupiah($cartTotal) ?></strong></div><form method="post"><?= csrf() ?><input type="hidden" name="action" value="checkout"><button class="button button-primary button-full" type="submit">Buat pesanan <span>↗</span></button></form><p class="tiny-note">Ongkir dikonfirmasi dengan penjual. Checkout demo mencatat pesanan dan mengurangi stok; pembayaran dan kurir belum terintegrasi.</p></aside>
        </div><?php endif; ?>
    </section>

<?php elseif ($page === 'orders'): ?>
    <?php if (!$user) { message('Masuk untuk melihat pesanan Anda.', 'error'); go('login'); }
    $statement = $pdo->prepare('SELECT o.id, o.total, o.created_at, GROUP_CONCAT(CONCAT(oi.product_name, " × ", oi.quantity) SEPARATOR ", ") AS items FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC');
    $statement->execute([(int) $user['id']]); $orders = $statement->fetchAll(); ?>
    <section class="simple-page"><div class="section-heading"><div><span class="eyebrow">RIWAYAT BELANJA</span><h1>Pesanan <em>kamu.</em></h1></div><a class="text-link" href="?page=home#produk">← Kembali belanja</a></div>
        <?php if (!$orders): ?><div class="empty-state"><span>📦</span><h2>Belum ada pesanan</h2><p>Produk pertama yang kamu suka tinggal selangkah lagi.</p></div>
        <?php else: ?><div class="orders-list"><?php foreach ($orders as $order): ?><article class="order-card"><div><span class="eyebrow">PESANAN #<?= (int) $order['id'] ?> · <?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?></span><p><?= e($order['items']) ?></p></div><strong><?= rupiah((int) $order['total']) ?></strong></article><?php endforeach; ?></div><?php endif; ?>
    </section>

<?php elseif ($page === 'dashboard'): ?>
    <?php if (!$user) { message('Masuk untuk membuka dashboard pelanggan.', 'error'); go('login'); }
    if ($user['role'] !== 'customer') { go('admin'); }
    $statement = $pdo->prepare('SELECT COUNT(*) AS order_count, COALESCE(SUM(total), 0) AS total_spent FROM orders WHERE user_id = ?');
    $statement->execute([(int) $user['id']]);
    $customerStats = $statement->fetch();
    $statement = $pdo->prepare('SELECT id, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5');
    $statement->execute([(int) $user['id']]);
    $recentOrders = $statement->fetchAll(); ?>
    <section class="simple-page customer-dashboard">
        <div class="section-heading"><div><span class="eyebrow">RUANG PELANGGAN</span><h1>Hai, <em><?= e(explode(' ', $user['name'])[0]) ?>.</em></h1><p class="section-subtitle">Ringkasan aktivitas dan pesanan kamu di Electra.</p></div><a class="button button-primary" href="?page=home#produk">Jelajahi produk <span>↗</span></a></div>
        <div class="stats-grid customer-stats"><article><span>Total pesanan</span><strong><?= (int) $customerStats['order_count'] ?></strong><small>pesanan dibuat</small></article><article><span>Total belanja</span><strong><?= rupiah((int) $customerStats['total_spent']) ?></strong><small>nilai pesanan tercatat</small></article><article><span>Keranjang</span><strong><?= (int) $cartCount ?></strong><small>unit siap checkout</small></article></div>
        <section class="dashboard-orders"><div class="inventory-heading"><div><span class="eyebrow">AKTIVITAS TERBARU</span><h2>Pesanan terbaru</h2></div><a class="text-link" href="?page=orders">Semua pesanan ↗</a></div>
            <?php if (!$recentOrders): ?><div class="empty-state compact"><span>📦</span><h2>Belum ada pesanan</h2><p>Pesanan yang kamu buat akan tampil di sini.</p><a class="button button-primary" href="?page=home#produk">Mulai belanja ↗</a></div>
            <?php else: ?><div class="orders-list"><?php foreach ($recentOrders as $order): ?><article class="order-card"><div><span class="eyebrow">PESANAN #<?= (int) $order['id'] ?></span><p><?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?></p></div><strong><?= rupiah((int) $order['total']) ?></strong></article><?php endforeach; ?></div><?php endif; ?>
        </section>
    </section>

<?php elseif ($page === 'product'): ?>
    <?php $productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $statement = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $statement->execute([$productId]);
    $product = $statement->fetch();
    if (!$product): http_response_code(404); ?>
        <section class="simple-page"><div class="empty-state"><span>🔎</span><h2>Produk tidak ditemukan</h2><p>Produk mungkin sudah tidak tersedia di katalog.</p><a class="button button-primary" href="?page=home#produk">Kembali ke katalog ↗</a></div></section>
    <?php else: $empty = (int) $product['stock'] === 0; ?>
        <section class="simple-page product-detail-page">
            <a class="text-link" href="?page=home&amp;category=<?= rawurlencode($product['category']) ?>#produk">← Kembali ke <?= e($product['category']) ?></a>
            <div class="product-detail-layout">
                <div class="product-detail-art tone-<?= (int) $product['id'] % 5 ?>"><span class="product-tag"><?= e($product['category']) ?></span><?php if ($image = productImage($product)): ?><img class="product-detail-image" src="<?= e($image) ?>" alt="<?= e($product['name']) ?>"><?php else: ?><span class="product-detail-emoji" aria-hidden="true"><?= e($product['icon']) ?></span><?php endif; ?><span class="art-orbit"></span></div>
                <div class="product-detail-copy"><span class="eyebrow">DETAIL PRODUK · <?= e($product['category']) ?></span><h1><?= e($product['name']) ?></h1><p class="product-detail-description"><?= e($product['description']) ?></p>
                    <div class="detail-stock"><span class="stock-dot <?= $empty ? 'is-empty' : '' ?>"></span><strong><?= $empty ? 'Stok habis' : 'Stok tersedia: ' . number_format((int) $product['stock']) . ' unit' ?></strong><small>Informasi stok diperbarui dari katalog toko.</small></div>
                    <div class="detail-price"><?= rupiah((int) $product['price']) ?></div>
                    <form method="post" class="detail-cart-form"><?= csrf() ?><input type="hidden" name="action" value="add_to_cart"><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><input type="hidden" name="return_to" value="product"><label>Jumlah<input type="number" name="quantity" value="1" min="1" max="<?= min(10, (int) $product['stock']) ?>" <?= $empty ? 'disabled' : '' ?>></label><button class="button button-primary" type="submit" <?= $empty ? 'disabled' : '' ?>>Tambah ke keranjang <span>↗</span></button></form>
                    <div class="shipping-info-card"><span aria-hidden="true">↗</span><div><strong>Informasi pengiriman</strong><p>Pengiriman pada situs demo ini belum terhubung ke kurir. Konfirmasi area, ongkir, dan estimasi langsung dengan penjual sebelum transaksi.</p><a class="text-link" href="?page=shipping">Lihat informasi pengiriman</a></div></div>
                </div>
            </div>
        </section>
    <?php endif; ?>

<?php elseif ($page === 'shipping'): ?>
    <section class="simple-page shipping-page">
        <div class="section-heading"><div><span class="eyebrow">INFO SEBELUM CHECKOUT</span><h1>Informasi <em>pengiriman.</em></h1><p class="section-subtitle">Ketentuan pengiriman untuk toko demo Electra.</p></div><a class="text-link" href="?page=home#produk">← Kembali ke katalog</a></div>
        <div class="shipping-info-grid"><article><span>01</span><h2>Konfirmasi alamat</h2><p>Hubungi penjual untuk memastikan area layanan dan alamat tujuan sebelum pembayaran.</p></article><article><span>02</span><h2>Ongkos kirim</h2><p>Ongkir belum dihitung otomatis. Minta konfirmasi biaya berdasarkan alamat dan pilihan kurir.</p></article><article><span>03</span><h2>Estimasi &amp; pelacakan</h2><p>Estimasi tiba dan nomor pelacakan belum tersedia otomatis pada situs demo ini. Penjual akan menginformasikannya setelah pengiriman.</p></article></div>
        <p class="shipping-demo-note">Catatan: checkout saat ini hanya mencatat pesanan dan mengurangi stok. Pembayaran serta integrasi kurir belum tersedia.</p>
    </section>

<?php elseif ($page === 'admin'): ?>
    <?php adminOnly(); $products = $pdo->query('SELECT * FROM products ORDER BY category, name')->fetchAll(); $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0; $editProduct = null;
    if ($editId) { $statement = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $statement->execute([$editId]); $editProduct = $statement->fetch() ?: null; }
    $adminOrders = $pdo->query('SELECT o.id, o.total, o.created_at, u.name AS customer_name, u.email AS customer_email, GROUP_CONCAT(CONCAT(oi.product_name, " × ", oi.quantity) ORDER BY oi.id SEPARATOR ", ") AS items FROM orders o JOIN users u ON u.id = o.user_id JOIN order_items oi ON oi.order_id = o.id GROUP BY o.id, o.total, o.created_at, u.name, u.email ORDER BY o.created_at DESC, o.id DESC LIMIT 20')->fetchAll(); ?>
    <section class="simple-page admin-page"><div class="section-heading"><div><span class="eyebrow">ELECTRA CONTROL ROOM</span><h1>Dashboard <em>admin.</em></h1></div><span class="admin-pill">● Sistem aktif</span></div>
        <div class="stats-grid"><article><span>Produk aktif</span><strong><?= $productCount ?></strong><small>item dalam katalog</small></article><article><span>Total unit stok</span><strong><?= number_format($stockCount) ?></strong><small>unit tersedia</small></article><article><span>Pesanan masuk</span><strong><?= $orderCount ?></strong><small>pesanan dibuat</small></article></div>
        <div class="admin-grid"><form method="post" class="admin-form"><?= csrf() ?><input type="hidden" name="action" value="save_product"><input type="hidden" name="product_id" value="<?= (int) ($editProduct['id'] ?? 0) ?>"><span class="eyebrow"><?= $editProduct ? 'EDIT PRODUK' : 'KATALOG BARU' ?></span><h2><?= $editProduct ? 'Perbarui produk' : 'Tambah produk' ?></h2>
            <label>Nama produk<input name="name" required maxlength="140" value="<?= e($editProduct['name'] ?? '') ?>" placeholder="Contoh: ASUS Zenbook 14"></label>
            <label>Kategori<select name="category" required><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= ($editProduct['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
            <label>Deskripsi<input name="description" required maxlength="255" value="<?= e($editProduct['description'] ?? '') ?>" placeholder="Highlight produk dalam satu kalimat"></label>
            <div class="form-row"><label>Harga (Rp)<input name="price" type="number" min="1" required value="<?= e($editProduct['price'] ?? '') ?>" placeholder="8999000"></label><label>Stok<input name="stock" type="number" min="0" required value="<?= e($editProduct['stock'] ?? '') ?>" placeholder="12"></label></div>
            <label>Ikon produk<input name="icon" maxlength="12" value="<?= e($editProduct['icon'] ?? '📦') ?>" placeholder="💻"></label><button class="button button-primary button-full" type="submit"><?= $editProduct ? 'Simpan perubahan' : 'Tambah ke katalog' ?> ↗</button><?php if ($editProduct): ?><a class="cancel-edit" href="?page=admin">Batal edit</a><?php endif; ?>
        </form><div class="inventory-panel"><div class="inventory-heading"><div><span class="eyebrow">LIVE INVENTORY</span><h2>Stok produk</h2></div><span><?= count($products) ?> item</span></div>
            <?php foreach ($products as $product): ?><article class="inventory-row"><span class="inventory-icon"><?= e($product['icon']) ?></span><div class="inventory-name"><strong><?= e($product['name']) ?></strong><small><?= e($product['category']) ?> · <?= rupiah((int) $product['price']) ?></small></div><span class="inventory-stock <?= (int) $product['stock'] < 5 ? 'low-stock' : '' ?>"><?= (int) $product['stock'] ?> unit</span><a class="edit-link" href="?page=admin&amp;edit=<?= (int) $product['id'] ?>">Edit</a></article><?php endforeach; ?>
        </div></div>
        <section class="admin-orders"><div class="inventory-heading"><div><span class="eyebrow">PESANAN TERBARU</span><h2>Pesanan pelanggan</h2></div><span><?= min($orderCount, 20) ?> terbaru dari <?= $orderCount ?></span></div>
            <?php if (!$adminOrders): ?><div class="empty-state compact"><span>📦</span><h2>Belum ada pesanan</h2><p>Pesanan pelanggan akan muncul di sini setelah checkout.</p></div>
            <?php else: ?><div class="orders-list"><?php foreach ($adminOrders as $order): ?><article class="order-card"><div class="admin-order-details"><span class="eyebrow">PESANAN #<?= (int) $order['id'] ?> · <?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?></span><strong><?= e($order['customer_name']) ?></strong><span class="muted"><?= e($order['customer_email']) ?></span><p><?= e($order['items']) ?></p></div><strong><?= rupiah((int) $order['total']) ?></strong></article><?php endforeach; ?></div><?php endif; ?>
        </section>
    </section>

<?php else: ?>
    <?php $selected = (string) ($_GET['category'] ?? 'Semua'); if (!in_array($selected, $allCategories, true)) { $selected = 'Semua'; }
    $search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100); $where = []; $params = [];
    if ($selected !== 'Semua') { $where[] = 'category = ?'; $params[] = $selected; }
    if ($search !== '') { $where[] = '(name LIKE ? OR description LIKE ?)'; $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%'; }
    $statement = $pdo->prepare('SELECT * FROM products' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id');
    $statement->execute($params); $products = $statement->fetchAll();
    $featured = $pdo->query('SELECT * FROM products WHERE stock > 0 ORDER BY stock DESC LIMIT 1')->fetch(); ?>
    <section class="hero"><div class="hero-copy"><span class="eyebrow"><span class="live-dot"></span> YOUR DAILY DOSE OF GOOD TECH</span><h1>Teknologi bagus.<br><em>Hidup makin seru.</em></h1><p>Gear pilihan buat kerja, main, dan semua hal di antaranya. Cuma yang worth it buat kamu.</p><div class="hero-actions"><a class="button button-primary" href="#produk">Cari gear favorit <span>↗</span></a><a class="hero-secondary" href="#kenapa">Kenalan sama kami <span>↓</span></a></div><div class="hero-proof"><div class="proof-avatars"><span>R</span><span>A</span><span>D</span></div><p><strong>1.200+</strong> tech lovers<br>sudah belanja di sini</p></div></div>
        <div class="hero-visual"><div class="hero-spark spark-one">✳</div><div class="hero-spark spark-two">✦</div><div class="hero-image-orb"><div class="orb-inner"><span><?= e($featured['icon'] ?? '💻') ?></span></div></div><div class="floating-card float-top"><span class="float-check">✓</span><div><strong>Tech picks</strong><small>Curated with care</small></div></div><div class="floating-card float-bottom"><span class="float-price-icon">↗</span><div><small>Mulai dari</small><strong><?= rupiah(1599000) ?></strong></div></div><span class="hero-caption">GEAR UP. SHOW UP.</span></div>
    </section>
    <section class="trust-strip"><div><span>✳</span> Produk pilihan</div><div><span>↗</span> Harga transparan</div><div><span>◉</span> Info stok real-time</div><div><span>♡</span> Belanja tanpa ribet</div></section>
    <section class="products-section" id="produk">    <div class="section-heading"><div><span class="eyebrow">THE GOOD STUFF</span><h2>Temukan <em>favoritmu.</em></h2><p class="section-subtitle">Pilihan teknologi yang pas buat kebutuhan dan gaya kamu.</p></div><form class="search-form" method="get"><input type="hidden" name="page" value="home"><label class="visually-hidden" for="search">Cari produk</label><input id="search" type="search" name="q" maxlength="100" value="<?= e($search) ?>" placeholder="Cari produk..."><button aria-label="Cari" type="submit">⌕</button></form></div>
        <div class="category-tabs"><?php foreach ($allCategories as $category): ?><a class="<?= $selected === $category ? 'active' : '' ?>" href="?page=home&amp;category=<?= rawurlencode($category) ?>&amp;q=<?= rawurlencode($search) ?>#produk"><?= e($category) ?></a><?php endforeach; ?></div>
        <?php if ($products): ?><div class="product-grid"><?php foreach ($products as $product) { productCard($product); } ?></div>
        <?php else: ?><div class="empty-state compact"><span>🔎</span><h2>Belum ada produk yang cocok</h2><p>Coba kata kunci atau kategori lain.</p><a class="text-link" href="?page=home#produk">Lihat semua produk</a></div><?php endif; ?>
        <p class="price-disclaimer">Harga katalog indikatif untuk 2026 dan dapat berubah. Stok mengikuti data toko demo; hubungi penjual untuk konfirmasi.</p>
    </section>
    <section class="why-section" id="kenapa"><div><span class="eyebrow">SHOP SMART, FEEL GOOD</span><h2>Belanja tech<br>tanpa <em>overthinking.</em></h2><p>Kami kurasi produk yang relevan, tampilkan harga dan jumlah stok dengan jelas, dan bikin kamu lebih gampang memilih.</p><a class="button button-light" href="#produk">Mulai eksplorasi <span>↗</span></a></div><div class="why-features"><article><span>01</span><div><h3>Kurasi, bukan kebanyakan pilihan</h3><p>Produk pilihan dari kategori yang paling kamu cari.</p></div></article><article><span>02</span><div><h3>Harga &amp; stok tampil jelas</h3><p>Tahu harga dan ketersediaan sebelum masuk keranjang.</p></div></article><article><span>03</span><div><h3>Checkout sesimpel itu</h3><p>Keranjang praktis, akun aman, pesanan tercatat.</p></div></article></div><span class="why-deco">e.</span></section>
<?php endif; ?>
</main>
<footer class="site-footer"><a class="brand footer-brand" href="?page=home"><img class="brand-mark" src="logo-electra.svg" alt=""> electra<span class="brand-period">.</span></a><p>Teknologi bagus buat hari-hari yang lebih seru.</p><span>© <?= date('Y') ?> Electra. Made for tech lovers.</span></footer>
</body>
</html>
