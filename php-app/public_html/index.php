<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/repo.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = request_path();
$pdo = db();

try {
    if ($path === '/' && $method === 'GET') {
        $categories = query_categories_with_counts($pdo);
        $products = query_home_products($pdo, 30);

        render_view('public/home', [
            'title' => cfg('app_name'),
            'currentPath' => '/',
            'categories' => $categories,
            'products' => $products,
            'metaDescription' => 'Katalog digital ' . cfg('app_name') . ' dengan preview produk cepat dan rapi.',
            'metaUrl' => app_url('/'),
        ], 'public');
        exit;
    }

    if ($path === '/katalog' && $method === 'GET') {
        $q = qv('q', '') ?? '';
        $categorySlug = qv('category', '') ?? '';
        $sort = normalize_catalog_sort(qv('sort', 'newest'));
        $requestedPage = parse_positive_int(qv('page', '1'), 1);

        $catalog = query_catalog_data($pdo, $q, $categorySlug, $sort, $requestedPage, 12);
        if ($catalog['requestedPage'] > $catalog['totalPages'] && (int) ($catalog['pagination']['totalProducts'] ?? 0) > 0) {
            $redirectUrl = build_catalog_url([
                'q' => $q,
                'category' => $catalog['selectedCategorySlug'],
                'sort' => $sort,
                'page' => $catalog['totalPages']
            ]);
            redirect_absolute($redirectUrl);
        }

        render_view('public/catalog', [
            'title' => 'Katalog Produk',
            'currentPath' => '/katalog',
            'q' => $q,
            'sort' => $sort,
            'selectedCategorySlug' => $catalog['selectedCategorySlug'],
            'products' => $catalog['products'],
            'categories' => $catalog['categories'],
            'categoryLinks' => $catalog['categoryLinks'],
            'sortLinks' => $catalog['sortLinks'],
            'pagination' => $catalog['pagination'],
            'metaDescription' => 'Cari katalog produk digital, filter kategori, dan pilih item yang kamu butuhkan.',
            'metaUrl' => app_url($_SERVER['REQUEST_URI'] ?? '/katalog'),
        ], 'public');
        exit;
    }

    if (preg_match('#^/c/([a-z0-9\-]+)$#', $path, $m) && $method === 'GET') {
        $slug = (string) $m[1];
        $stmt = $pdo->prepare('SELECT id, slug FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $category = $stmt->fetch();
        if (!$category) {
            render_public_error(404, 'Kategori tidak ditemukan.', $path);
            exit;
        }

        redirect_absolute(build_catalog_url(['category' => (string) $category['slug']]));
    }

    if (preg_match('#^/p/([a-z0-9\-]+)$#', $path, $m) && $method === 'GET') {
        $slug = (string) $m[1];
        $product = query_public_product_by_slug($pdo, $slug);
        if (!$product) {
            render_public_error(404, 'Produk tidak ditemukan.', $path);
            exit;
        }

        $pdo->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = :id')->execute(['id' => (int) $product['id']]);
        $product['view_count'] = (int) ($product['view_count'] ?? 0) + 1;

        $images = query_product_images($pdo, (int) $product['id']);
        if (!empty($images[0]['image_path'])) {
            $product['image_path'] = $images[0]['image_path'];
        }

        $product = attach_product_meta($product);
        $relatedProducts = query_related_products($pdo, $product, 4);

        $shareUrl = app_url('/p/' . $slug);
        $ogImage = trim((string) ($product['og_image_path'] ?? ''));
        if ($ogImage === '' && !empty($product['image_path'])) {
            $ogImage = (string) $product['image_path'];
        }
        $ogImageUrl = $ogImage !== '' ? app_url($ogImage) : '';

        $plainDescription = trim((string) ($product['description'] ?? ''));
        $metaDescription = $plainDescription !== ''
            ? mb_substr($plainDescription, 0, 160)
            : ('Preview ' . (string) ($product['title'] ?? 'Produk') . ' di ' . cfg('app_name') . ', langsung lanjut order via Telegram.');

        render_view('public/detail', [
            'title' => (string) ($product['title'] ?? 'Detail Produk'),
            'currentPath' => '/p/' . $slug,
            'product' => $product,
            'productImages' => $images,
            'relatedProducts' => $relatedProducts,
            'tgTrackUrl' => (string) ($product['tg_track_url'] ?? '#'),
            'shareUrl' => $shareUrl,
            'metaDescription' => $metaDescription,
            'metaUrl' => $shareUrl,
            'ogImageUrl' => $ogImageUrl,
        ], 'public');
        exit;
    }

    if (preg_match('#^/go/tg/([a-z0-9\-]+)$#', $path, $m) && $method === 'GET') {
        $slug = (string) $m[1];
        $product = query_public_product_by_slug($pdo, $slug);
        if (!$product) {
            redirect_local('/katalog');
        }

        $pdo->prepare('UPDATE products SET telegram_click_count = telegram_click_count + 1 WHERE id = :id')
            ->execute(['id' => (int) $product['id']]);

        $tgUrl = build_telegram_url($product['whatsapp_number'] ?? null, build_product_message($product));
        if ($tgUrl === '#') {
            redirect_local('/p/' . $slug);
        }

        redirect_absolute($tgUrl);
    }
    if ($path === '/admin/login' && $method === 'GET') {
        if (admin_user()) {
            redirect_local('/admin');
        }

        render_view('admin/login', [
            'title' => 'Login Admin',
            'currentPath' => '/admin/login',
            'errorMessage' => '',
        ], 'admin');
        exit;
    }

    if ($path === '/admin/login' && $method === 'POST') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (attempt_admin_login($username, $password)) {
            redirect_local('/admin');
        }

        render_view('admin/login', [
            'title' => 'Login Admin',
            'currentPath' => '/admin/login',
            'errorMessage' => 'Username atau password salah.',
        ], 'admin');
        exit;
    }

    if ($path === '/admin/logout' && $method === 'POST') {
        admin_logout();
        redirect_local('/admin/login');
    }

    if (str_starts_with($path, '/admin')) {
        require_admin_login();
    }

    if ($path === '/admin' && $method === 'GET') {
        $dashboard = query_admin_stats($pdo);

        render_view('admin/dashboard', [
            'title' => 'Dashboard',
            'currentPath' => '/admin',
            'stats' => $dashboard['stats'],
            'topProducts' => $dashboard['topProducts'],
            'currentAdmin' => admin_user(),
        ], 'admin');
        exit;
    }

    if ($path === '/admin/products' && $method === 'GET') {
        $products = query_admin_products($pdo);

        render_view('admin/products', [
            'title' => 'Produk',
            'currentPath' => '/admin/products',
            'products' => $products,
            'errorMessage' => qv('error', '') ?? '',
            'successMessage' => qv('success', '') ?? '',
            'currentAdmin' => admin_user(),
        ], 'admin');
        exit;
    }

    if ($path === '/admin/products/new' && $method === 'GET') {
        render_admin_product_form('create', [
            'id' => null,
            'title' => '',
            'description' => '',
            'category_id' => 0,
            'price' => 0,
            'badge_status' => 'none',
            'publish_at' => null,
            'whatsapp_number' => cfg('telegram_default'),
            'is_active' => 1,
            'images' => [],
        ]);
        exit;
    }

    if ($path === '/admin/products' && $method === 'POST') {
        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            render_admin_product_form('create', [
                'id' => null, 'title' => '', 'description' => '', 'category_id' => 0,
                'price' => 0, 'badge_status' => 'none', 'publish_at' => null,
                'whatsapp_number' => cfg('telegram_default'), 'is_active' => 1, 'images' => [],
            ], 'Upload gagal: ukuran file terlalu besar. Kurangi jumlah atau ukuran gambar.');
            exit;
        }

        $payload = parse_product_input();
        $uploadedFiles = normalize_upload_files($_FILES['images'] ?? []);

        if ($payload['title'] === '' || $payload['category_id'] <= 0) {
            $payload['images'] = [];
            render_admin_product_form('create', $payload, 'Title dan kategori wajib diisi.');
            exit;
        }

        if ($uploadedFiles === []) {
            $payload['images'] = [];
            render_admin_product_form('create', $payload, 'Minimal 1 gambar harus di-upload.');
            exit;
        }

        $savedUploads = [];
        try {
            $slug = unique_product_slug($pdo, $payload['title']);
            $savedUploads = process_uploaded_images($uploadedFiles, $slug, 0);

            if ($savedUploads === []) {
                throw new RuntimeException('Minimal 1 gambar harus di-upload.');
            }

            $pdo->beginTransaction();
            $insertProduct = $pdo->prepare('
                INSERT INTO products
                    (category_id, title, slug, description, price, whatsapp_number, is_active, badge_status, publish_at, view_count, telegram_click_count, createdAt, updatedAt)
                VALUES
                    (:category_id, :title, :slug, :description, :price, :whatsapp_number, :is_active, :badge_status, :publish_at, 0, 0, NOW(), NOW())
            ');
            $insertProduct->execute([
                'category_id' => $payload['category_id'],
                'title' => $payload['title'],
                'slug' => $slug,
                'description' => $payload['description'],
                'price' => 0,
                'whatsapp_number' => $payload['whatsapp_number'],
                'is_active' => $payload['is_active'],
                'badge_status' => $payload['badge_status'],
                'publish_at' => $payload['publish_at'],
            ]);

            $productId = (int) $pdo->lastInsertId();
            $insertImage = $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order, createdAt) VALUES (:product_id, :image_path, :sort_order, NOW())');
            foreach ($savedUploads as $item) {
                $insertImage->execute([
                    'product_id' => $productId,
                    'image_path' => (string) $item['image_path'],
                    'sort_order' => (int) $item['sort_order'],
                ]);
            }

            $pdo->commit();
            redirect_local('/admin/products?success=' . rawurlencode('Produk berhasil ditambahkan'));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            cleanup_saved_uploads($savedUploads);
            $payload['images'] = [];
            render_admin_product_form('create', $payload, $e->getMessage());
            exit;
        }
    }
    if (preg_match('#^/admin/products/(\d+)/edit$#', $path, $m) && $method === 'GET') {
        $id = (int) $m[1];
        $product = query_admin_product_by_id($pdo, $id);
        if (!$product) {
            redirect_local('/admin/products?error=' . rawurlencode('Produk tidak ditemukan'));
        }

        render_admin_product_form('edit', $product);
        exit;
    }

    if (preg_match('#^/admin/products/(\d+)$#', $path, $m) && $method === 'POST') {
        $id = (int) $m[1];
        $product = query_admin_product_by_id($pdo, $id);
        if (!$product) {
            redirect_local('/admin/products?error=' . rawurlencode('Produk tidak ditemukan'));
        }

        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            render_admin_product_form('edit', $product, 'Upload gagal: ukuran file terlalu besar. Kurangi jumlah atau ukuran gambar.');
            exit;
        }

        $payload = parse_product_input();
        $uploadedFiles = normalize_upload_files($_FILES['images'] ?? []);
        $removeIds = $_POST['removeImageIds'] ?? [];
        if (!is_array($removeIds)) {
            $removeIds = [$removeIds];
        }

        $removeIdMap = [];
        foreach ($removeIds as $rid) {
            $ridInt = (int) $rid;
            if ($ridInt > 0) {
                $removeIdMap[$ridInt] = true;
            }
        }

        $existingImages = $product['images'] ?? [];
        $imagesToKeep = [];
        $imagesToRemove = [];
        foreach ($existingImages as $img) {
            $imgId = (int) ($img['id'] ?? 0);
            if (isset($removeIdMap[$imgId])) {
                $imagesToRemove[] = $img;
            } else {
                $imagesToKeep[] = $img;
            }
        }

        if ($payload['title'] === '' || $payload['category_id'] <= 0) {
            $productDraft = array_merge($product, $payload);
            $productDraft['images'] = $existingImages;
            render_admin_product_form('edit', $productDraft, 'Title dan kategori wajib diisi.');
            exit;
        }

        if (count($imagesToKeep) + count($uploadedFiles) < 1) {
            $productDraft = array_merge($product, $payload);
            $productDraft['images'] = $existingImages;
            render_admin_product_form('edit', $productDraft, 'Produk harus punya minimal 1 gambar.');
            exit;
        }

        $savedUploads = [];
        try {
            $slug = unique_product_slug($pdo, $payload['title'], $id);
            $maxSort = 0;
            foreach ($imagesToKeep as $img) {
                $maxSort = max($maxSort, (int) ($img['sort_order'] ?? 0));
            }
            $savedUploads = process_uploaded_images($uploadedFiles, $slug, $maxSort);

            $pdo->beginTransaction();
            $update = $pdo->prepare('
                UPDATE products
                SET category_id = :category_id,
                    title = :title,
                    slug = :slug,
                    description = :description,
                    price = :price,
                    whatsapp_number = :whatsapp_number,
                    is_active = :is_active,
                    badge_status = :badge_status,
                    publish_at = :publish_at,
                    updatedAt = NOW()
                WHERE id = :id
            ');
            $update->execute([
                'category_id' => $payload['category_id'],
                'title' => $payload['title'],
                'slug' => $slug,
                'description' => $payload['description'],
                'price' => 0,
                'whatsapp_number' => $payload['whatsapp_number'],
                'is_active' => $payload['is_active'],
                'badge_status' => $payload['badge_status'],
                'publish_at' => $payload['publish_at'],
                'id' => $id,
            ]);

            if ($imagesToRemove !== []) {
                $ids = array_map(static fn(array $img): int => (int) $img['id'], $imagesToRemove);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $deleteSql = "DELETE FROM product_images WHERE product_id = ? AND id IN ({$placeholders})";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute(array_merge([$id], $ids));
            }

            if ($savedUploads !== []) {
                $insertImage = $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order, createdAt) VALUES (:product_id, :image_path, :sort_order, NOW())');
                foreach ($savedUploads as $item) {
                    $insertImage->execute([
                        'product_id' => $id,
                        'image_path' => (string) $item['image_path'],
                        'sort_order' => (int) $item['sort_order'],
                    ]);
                }
            }

            $pdo->commit();

            if ($imagesToRemove !== []) {
                $paths = array_map(static fn(array $img): string => (string) ($img['image_path'] ?? ''), $imagesToRemove);
                remove_public_files($paths);
            }

            redirect_local('/admin/products?success=' . rawurlencode('Produk berhasil diupdate'));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            cleanup_saved_uploads($savedUploads);
            $productDraft = array_merge($product, $payload);
            $productDraft['images'] = $existingImages;
            render_admin_product_form('edit', $productDraft, $e->getMessage());
            exit;
        }
    }

    if (preg_match('#^/admin/products/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        $id = (int) $m[1];
        $product = query_admin_product_by_id($pdo, $id);
        if (!$product) {
            redirect_local('/admin/products?error=' . rawurlencode('Produk tidak ditemukan'));
        }

        $imagePaths = array_map(static fn(array $img): string => (string) ($img['image_path'] ?? ''), $product['images'] ?? []);
        $ogPath = trim((string) ($product['og_image_path'] ?? ''));

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM product_images WHERE product_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM products WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirect_local('/admin/products?error=' . rawurlencode('Gagal menghapus produk'));
        }

        if ($ogPath !== '') {
            $imagePaths[] = $ogPath;
        }
        remove_public_files($imagePaths);

        redirect_local('/admin/products?success=' . rawurlencode('Produk berhasil dihapus'));
    }

    if (preg_match('#^/admin/products/(\d+)/toggle$#', $path, $m) && $method === 'POST') {
        $id = (int) $m[1];
        $stmt = $pdo->prepare('UPDATE products SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END, updatedAt = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() < 1) {
            redirect_local('/admin/products?error=' . rawurlencode('Produk tidak ditemukan'));
        }

        redirect_local('/admin/products?success=' . rawurlencode('Status produk diupdate'));
    }

    if ($path === '/admin/categories' && $method === 'GET') {
        $categories = query_admin_categories($pdo);

        render_view('admin/categories', [
            'title' => 'Kategori',
            'currentPath' => '/admin/categories',
            'categories' => $categories,
            'errorMessage' => qv('error', '') ?? '',
            'successMessage' => qv('success', '') ?? '',
            'currentAdmin' => admin_user(),
        ], 'admin');
        exit;
    }

    if ($path === '/admin/categories' && $method === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            redirect_local('/admin/categories?error=' . rawurlencode('Nama kategori wajib diisi'));
        }

        $slug = unique_category_slug($pdo, $name);
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, createdAt) VALUES (:name, :slug, NOW())');
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
        ]);

        redirect_local('/admin/categories?success=' . rawurlencode('Kategori berhasil ditambah'));
    }

    if (preg_match('#^/admin/categories/(\d+)$#', $path, $m) && $method === 'POST') {
        $id = (int) $m[1];
        $name = trim((string) ($_POST['name'] ?? ''));

        $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            redirect_local('/admin/categories?error=' . rawurlencode('Kategori tidak ditemukan'));
        }

        if ($name === '') {
            redirect_local('/admin/categories?error=' . rawurlencode('Nama kategori wajib diisi'));
        }

        $slug = unique_category_slug($pdo, $name, $id);
        $update = $pdo->prepare('UPDATE categories SET name = :name, slug = :slug WHERE id = :id');
        $update->execute(['name' => $name, 'slug' => $slug, 'id' => $id]);

        redirect_local('/admin/categories?success=' . rawurlencode('Kategori berhasil diupdate'));
    }

    if (preg_match('#^/admin/categories/(\d+)/delete$#', $path, $m) && $method === 'POST') {
        $id = (int) $m[1];
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');

        try {
            $stmt->execute(['id' => $id]);
            if ($stmt->rowCount() < 1) {
                redirect_local('/admin/categories?error=' . rawurlencode('Kategori tidak ditemukan'));
            }
            redirect_local('/admin/categories?success=' . rawurlencode('Kategori berhasil dihapus'));
        } catch (Throwable $e) {
            redirect_local('/admin/categories?error=' . rawurlencode('Kategori tidak bisa dihapus (masih dipakai produk).'));
        }
    }

    render_public_error(404, 'Halaman tidak ditemukan.', $path);
} catch (Throwable $e) {
    render_public_error(500, $e->getMessage(), $path);
}

