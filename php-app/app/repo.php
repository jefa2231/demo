<?php

declare(strict_types=1);

if (!function_exists('redirect_local')) {
    function redirect_local(string $path): never
    {
        header('Location: ' . local_url($path));
        exit;
    }

    function redirect_absolute(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    function query_categories_with_counts(PDO $pdo): array
    {
        $sql = '
            SELECT
                c.id,
                c.name,
                c.slug,
                COUNT(p.id) AS total
            FROM categories c
            LEFT JOIN products p
                ON p.category_id = c.id
                AND p.is_active = 1
                AND (p.publish_at IS NULL OR p.publish_at <= NOW())
            GROUP BY c.id, c.name, c.slug
            ORDER BY c.name ASC
        ';

        $rows = $pdo->query($sql)->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $row['total'] = (int) ($row['total'] ?? 0);
        }
        return $rows;
    }

    function query_admin_categories(PDO $pdo): array
    {
        $sql = 'SELECT id, name, slug, createdAt FROM categories ORDER BY createdAt DESC';
        return $pdo->query($sql)->fetchAll() ?: [];
    }

    function query_home_products(PDO $pdo, int $limit = 20): array
    {
        $sql = '
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image_path
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1
              AND (p.publish_at IS NULL OR p.publish_at <= NOW())
            ORDER BY p.createdAt DESC
            LIMIT :limit
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static fn(array $row): array => attach_product_meta($row), $rows);
    }

    function query_catalog_data(PDO $pdo, string $q, string $categorySlug, string $sort, int $requestedPage, int $perPage = 12): array
    {
        $selectedCategory = null;
        if ($categorySlug !== '') {
            $stmt = $pdo->prepare('SELECT id, name, slug FROM categories WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $categorySlug]);
            $selectedCategory = $stmt->fetch() ?: null;
        }

        $whereParts = ['p.is_active = 1', '(p.publish_at IS NULL OR p.publish_at <= NOW())'];
        $params = [];

        if ($q !== '') {
            $whereParts[] = 'p.title LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        if ($selectedCategory) {
            $whereParts[] = 'p.category_id = :category_id';
            $params['category_id'] = (int) $selectedCategory['id'];
        }

        $whereSql = implode(' AND ', $whereParts);
        $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM products p WHERE {$whereSql}");
        $countStmt->execute($params);
        $totalProducts = (int) (($countStmt->fetch()['total'] ?? 0));

        $totalPages = $totalProducts > 0 ? (int) ceil($totalProducts / $perPage) : 1;
        $currentPage = max(1, min($requestedPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        $orderSql = $sort === 'oldest' ? 'p.createdAt ASC' : 'p.createdAt DESC';

        $listSql = "
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image_path
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE {$whereSql}
            ORDER BY {$orderSql}
            LIMIT :limit OFFSET :offset
        ";

        $listStmt = $pdo->prepare($listSql);
        foreach ($params as $k => $v) {
            $listStmt->bindValue(':' . $k, $v);
        }
        $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();
        $products = $listStmt->fetchAll() ?: [];
        $products = array_map(static fn(array $row): array => attach_product_meta($row), $products);

        $categories = query_categories_with_counts($pdo);
        $activeCategorySlug = $selectedCategory ? (string) $selectedCategory['slug'] : '';
        $totalActiveProducts = 0;
        foreach ($categories as $cat) {
            $totalActiveProducts += (int) ($cat['total'] ?? 0);
        }

        $categoryLinks = [[
            'name' => 'Semua',
            'slug' => '',
            'total' => $totalActiveProducts,
            'active' => $activeCategorySlug === '',
            'url' => build_catalog_url(['q' => $q, 'sort' => $sort])
        ]];

        foreach ($categories as $category) {
            $categoryLinks[] = [
                'name' => (string) $category['name'],
                'slug' => (string) $category['slug'],
                'total' => (int) $category['total'],
                'active' => $activeCategorySlug === (string) $category['slug'],
                'url' => build_catalog_url([
                    'q' => $q,
                    'category' => (string) $category['slug'],
                    'sort' => $sort
                ])
            ];
        }

        $sortLinks = [
            [
                'key' => 'newest',
                'label' => 'Terbaru',
                'active' => $sort === 'newest',
                'url' => build_catalog_url([
                    'q' => $q,
                    'category' => $activeCategorySlug,
                    'sort' => 'newest'
                ])
            ],
            [
                'key' => 'oldest',
                'label' => 'Terlama',
                'active' => $sort === 'oldest',
                'url' => build_catalog_url([
                    'q' => $q,
                    'category' => $activeCategorySlug,
                    'sort' => 'oldest'
                ])
            ],
        ];

        $pagination = [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'hasPrev' => $currentPage > 1,
            'hasNext' => $currentPage < $totalPages,
            'prevUrl' => build_catalog_url([
                'q' => $q,
                'category' => $activeCategorySlug,
                'sort' => $sort,
                'page' => $currentPage - 1
            ]),
            'nextUrl' => build_catalog_url([
                'q' => $q,
                'category' => $activeCategorySlug,
                'sort' => $sort,
                'page' => $currentPage + 1
            ]),
            'pages' => []
        ];

        $pageStart = max(1, $currentPage - 2);
        $pageEnd = min($totalPages, $currentPage + 2);
        for ($page = $pageStart; $page <= $pageEnd; $page++) {
            $pagination['pages'][] = [
                'page' => $page,
                'active' => $page === $currentPage,
                'url' => build_catalog_url([
                    'q' => $q,
                    'category' => $activeCategorySlug,
                    'sort' => $sort,
                    'page' => $page
                ])
            ];
        }

        return [
            'selectedCategory' => $selectedCategory,
            'selectedCategorySlug' => $activeCategorySlug,
            'products' => $products,
            'categories' => $categories,
            'categoryLinks' => $categoryLinks,
            'sortLinks' => $sortLinks,
            'pagination' => $pagination,
            'requestedPage' => $requestedPage,
            'totalPages' => $totalPages,
        ];
    }
    function query_public_product_by_slug(PDO $pdo, string $slug): ?array
    {
        $sql = '
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.slug = :slug
              AND p.is_active = 1
              AND (p.publish_at IS NULL OR p.publish_at <= NOW())
            LIMIT 1
        ';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return $row;
    }

    function query_product_images(PDO $pdo, int $productId): array
    {
        $stmt = $pdo->prepare('SELECT id, image_path, sort_order FROM product_images WHERE product_id = :id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll() ?: [];
    }

    function query_related_products(PDO $pdo, array $sourceProduct, int $limit = 4): array
    {
        $sourceKeywords = extract_keywords((string) ($sourceProduct['title'] ?? ''));
        $whereParts = [
            'p.is_active = 1',
            '(p.publish_at IS NULL OR p.publish_at <= NOW())',
            'p.id <> :source_id'
        ];
        $params = ['source_id' => (int) ($sourceProduct['id'] ?? 0)];

        $sourceCategoryId = (int) ($sourceProduct['category_id'] ?? 0);
        if ($sourceCategoryId > 0) {
            if ($sourceKeywords !== []) {
                $orParts = ['p.category_id = :source_category'];
                $params['source_category'] = $sourceCategoryId;
                foreach ($sourceKeywords as $idx => $keyword) {
                    $key = 'kw' . $idx;
                    $orParts[] = "p.title LIKE :{$key}";
                    $params[$key] = '%' . $keyword . '%';
                }
                $whereParts[] = '(' . implode(' OR ', $orParts) . ')';
            } else {
                $whereParts[] = 'p.category_id = :source_category';
                $params['source_category'] = $sourceCategoryId;
            }
        }

        $sql = '
            SELECT
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image_path
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $whereParts) . '
            ORDER BY p.createdAt DESC
            LIMIT 40
        ';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        $candidates = $stmt->fetchAll() ?: [];

        $scored = [];
        foreach ($candidates as $candidate) {
            $candidate = attach_product_meta($candidate);
            $score = related_score($sourceProduct, $candidate, $sourceKeywords);
            $scored[] = ['score' => $score, 'product' => $candidate];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $result = [];
        foreach (array_slice($scored, 0, $limit) as $entry) {
            $result[] = $entry['product'];
        }

        return $result;
    }

    function query_admin_stats(PDO $pdo): array
    {
        $productsCount = (int) ($pdo->query('SELECT COUNT(*) AS c FROM products')->fetch()['c'] ?? 0);
        $categoriesCount = (int) ($pdo->query('SELECT COUNT(*) AS c FROM categories')->fetch()['c'] ?? 0);
        $activeProductsCount = (int) ($pdo->query('SELECT COUNT(*) AS c FROM products WHERE is_active = 1')->fetch()['c'] ?? 0);
        $scheduledProductsCount = (int) ($pdo->query('SELECT COUNT(*) AS c FROM products WHERE is_active = 1 AND publish_at IS NOT NULL AND publish_at > NOW()')->fetch()['c'] ?? 0);

        $totals = $pdo->query('SELECT COALESCE(SUM(view_count), 0) AS total_views, COALESCE(SUM(telegram_click_count), 0) AS total_clicks FROM products')->fetch() ?: [];
        $totalViews = (int) ($totals['total_views'] ?? 0);
        $totalClicks = (int) ($totals['total_clicks'] ?? 0);
        $totalCtr = $totalViews > 0 ? ($totalClicks / $totalViews) * 100 : 0;

        $topSql = '
            SELECT id, title, slug, view_count, telegram_click_count, badge_status, publish_at
            FROM products
            WHERE is_active = 1
            ORDER BY telegram_click_count DESC, view_count DESC, createdAt DESC
            LIMIT 8
        ';

        $rows = $pdo->query($topSql)->fetchAll() ?: [];
        $topProducts = [];
        foreach ($rows as $idx => $row) {
            $views = (int) ($row['view_count'] ?? 0);
            $clicks = (int) ($row['telegram_click_count'] ?? 0);
            $topProducts[] = [
                'rank' => $idx + 1,
                'id' => (int) $row['id'],
                'slug' => (string) $row['slug'],
                'title' => (string) $row['title'],
                'views' => $views,
                'clicks' => $clicks,
                'ctr' => $views > 0 ? ($clicks / $views) * 100 : 0,
                'badge_status' => (string) ($row['badge_status'] ?? 'none'),
                'publish_at' => $row['publish_at'] ?? null,
            ];
        }

        return [
            'stats' => [
                'productsCount' => $productsCount,
                'categoriesCount' => $categoriesCount,
                'activeProductsCount' => $activeProductsCount,
                'scheduledProductsCount' => $scheduledProductsCount,
                'totalViews' => $totalViews,
                'totalClicks' => $totalClicks,
                'totalCtr' => $totalCtr,
            ],
            'topProducts' => $topProducts,
        ];
    }

    function query_admin_products(PDO $pdo): array
    {
        $sql = '
            SELECT
                p.*,
                c.name AS category_name,
                (
                    SELECT pi.image_path
                    FROM product_images pi
                    WHERE pi.product_id = p.id
                    ORDER BY pi.sort_order ASC, pi.id ASC
                    LIMIT 1
                ) AS image_path,
                (
                    SELECT COUNT(*)
                    FROM product_images pi2
                    WHERE pi2.product_id = p.id
                ) AS image_total
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            ORDER BY p.createdAt DESC
        ';

        return $pdo->query($sql)->fetchAll() ?: [];
    }

    function query_admin_product_by_id(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        $product['images'] = query_product_images($pdo, $id);
        return $product;
    }
    function normalize_upload_files(array $fileInput): array
    {
        if (!isset($fileInput['name'])) {
            return [];
        }

        $normalized = [];
        if (is_array($fileInput['name'])) {
            $total = count($fileInput['name']);
            for ($i = 0; $i < $total; $i++) {
                $error = $fileInput['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $normalized[] = [
                    'name' => (string) ($fileInput['name'][$i] ?? ''),
                    'type' => (string) ($fileInput['type'][$i] ?? ''),
                    'tmp_name' => (string) ($fileInput['tmp_name'][$i] ?? ''),
                    'error' => (int) $error,
                    'size' => (int) ($fileInput['size'][$i] ?? 0),
                ];
            }
        } else {
            $error = (int) ($fileInput['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_NO_FILE) {
                $normalized[] = [
                    'name' => (string) ($fileInput['name'] ?? ''),
                    'type' => (string) ($fileInput['type'] ?? ''),
                    'tmp_name' => (string) ($fileInput['tmp_name'] ?? ''),
                    'error' => $error,
                    'size' => (int) ($fileInput['size'] ?? 0),
                ];
            }
        }

        return $normalized;
    }

    function process_uploaded_images(array $uploadedFiles, string $slug, int $startSort = 0): array
    {
        if ($uploadedFiles === []) {
            return [];
        }

        if (count($uploadedFiles) > 10) {
            throw new RuntimeException('Maksimal 10 gambar per produk.');
        }

        $uploadDir = cfg('public_dir') . '/uploads/products';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Folder upload tidak bisa dibuat.');
        }

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $timestamp = (int) round(microtime(true) * 1000);
        $saved = [];

        foreach ($uploadedFiles as $idx => $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Gagal upload salah satu file gambar.');
            }

            if ((int) ($file['size'] ?? 0) > (2 * 1024 * 1024)) {
                throw new RuntimeException('Ukuran gambar maksimal 2MB per file.');
            }

            $tmpName = (string) ($file['tmp_name'] ?? '');
            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                throw new RuntimeException('File upload tidak valid.');
            }

            $mime = (string) $finfo->file($tmpName);
            if (!isset($allowedMime[$mime])) {
                throw new RuntimeException('Format gambar hanya JPG, PNG, WEBP.');
            }

            $extension = $allowedMime[$mime];
            $filename = to_slug($slug) . '-' . $timestamp . '-' . ($startSort + $idx + 1) . '.' . $extension;
            $destPath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($tmpName, $destPath)) {
                throw new RuntimeException('Tidak bisa menyimpan gambar upload.');
            }

            $saved[] = [
                'image_path' => '/uploads/products/' . $filename,
                'sort_order' => $startSort + $idx + 1,
                'absolute_path' => $destPath,
            ];
        }

        return $saved;
    }

    function cleanup_saved_uploads(array $saved): void
    {
        foreach ($saved as $item) {
            $abs = (string) ($item['absolute_path'] ?? '');
            if ($abs !== '' && is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    function remove_public_files(array $paths): void
    {
        foreach ($paths as $path) {
            $path = (string) $path;
            if ($path === '' || !str_starts_with($path, '/uploads/')) {
                continue;
            }

            $full = cfg('public_dir') . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    function unique_category_slug(PDO $pdo, string $name, ?int $excludeId = null): string
    {
        $base = to_slug($name);
        if ($base === '') {
            $base = 'kategori';
        }

        $slug = $base;
        $i = 1;
        while (true) {
            if ($excludeId) {
                $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug AND id <> :id LIMIT 1');
                $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
                $stmt->execute(['slug' => $slug]);
            }

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . $i;
            $i++;
        }
    }

    function parse_product_input(): array
    {
        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'category_id' => (int) ($_POST['categoryId'] ?? 0),
            'badge_status' => normalize_badge_status((string) ($_POST['badgeStatus'] ?? 'none')),
            'publish_at' => parse_upload_datetime((string) ($_POST['publishAt'] ?? '')),
            'whatsapp_number' => trim((string) ($_POST['whatsappNumber'] ?? cfg('telegram_default'))),
            'is_active' => parse_checkbox($_POST['isActive'] ?? null) ? 1 : 0,
            'price' => 0,
        ];
    }

    function render_admin_product_form(string $mode, array $product, string $errorMessage = '', string $successMessage = ''): void
    {
        $pdo = db();
        $categories = query_admin_categories($pdo);

        render_view('admin/product_form', [
            'title' => $mode === 'create' ? 'Tambah Produk' : ('Edit ' . ($product['title'] ?? 'Produk')),
            'currentPath' => '/admin/products',
            'mode' => $mode,
            'product' => $product,
            'categories' => $categories,
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage,
            'currentAdmin' => admin_user(),
        ], 'admin');
    }

    function render_public_error(int $statusCode, string $message, string $currentPath = '/'): void
    {
        http_response_code($statusCode);
        render_view('public/error', [
            'title' => $statusCode === 404 ? '404' : 'Error',
            'errorMessage' => $message,
            'currentPath' => $currentPath,
        ], 'public');
    }
}

