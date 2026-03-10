<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!function_exists('cfg')) {
    function cfg(string $key, mixed $default = null): mixed
    {
        global $config;
        return $config[$key] ?? $default;
    }

    function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    function now_mysql(): string
    {
        return date('Y-m-d H:i:s');
    }

    function to_slug(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
        $text = preg_replace('/[\s-]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'produk';
    }

    function unique_product_slug(PDO $pdo, string $title, ?int $excludeId = null): string
    {
        $base = to_slug($title);
        $slug = $base;
        $i = 1;

        while (true) {
            if ($excludeId) {
                $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = :slug AND id <> :id LIMIT 1');
                $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
            } else {
                $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');
                $stmt->execute(['slug' => $slug]);
            }

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . $i;
            $i++;
        }
    }

    function badge_meta(string $status, bool $isNew = false): ?array
    {
        $status = strtolower(trim($status));
        if ($status === 'none' && $isNew) {
            $status = 'new';
        }

        $map = [
            'hot' => ['label' => 'Hot', 'class' => 'badge-hot'],
            'ready' => ['label' => 'Ready', 'class' => 'badge-ready'],
            'limited' => ['label' => 'Limited', 'class' => 'badge-limited'],
            'new' => ['label' => 'New', 'class' => 'badge-new'],
        ];

        return $map[$status] ?? null;
    }

    function parse_datetime_local(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    function product_is_visible(array $row): bool
    {
        $isActive = (int) ($row['is_active'] ?? 0) === 1;
        if (!$isActive) {
            return false;
        }

        $publishAt = $row['publish_at'] ?? null;
        if ($publishAt === null || $publishAt === '') {
            return true;
        }

        return strtotime((string) $publishAt) <= time();
    }

    function is_new_product(?string $createdAt): bool
    {
        if (!$createdAt) {
            return false;
        }
        $ts = strtotime($createdAt);
        if ($ts === false) {
            return false;
        }
        return (time() - $ts) <= (7 * 24 * 60 * 60);
    }

    function normalize_telegram_target(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return trim((string) cfg('telegram_default', 't.me/jefa14'));
        }

        if (preg_match('/^https?:\/\//i', $raw)) {
            return preg_replace('/^http:\/\//i', 'https://', $raw) ?? $raw;
        }

        if (str_starts_with($raw, 't.me/')) {
            return 'https://' . $raw;
        }

        if (str_starts_with($raw, '@')) {
            return 'https://t.me/' . substr($raw, 1);
        }

        if (preg_match('/^[0-9+\s-]+$/', $raw)) {
            return 'https://' . (cfg('telegram_default', 't.me/jefa14'));
        }

        return 'https://t.me/' . $raw;
    }

    function build_product_message(array $product): string
    {
        $title = trim((string) ($product['title'] ?? 'Produk'));
        $slug = trim((string) ($product['slug'] ?? ''));
        return "Halo, saya mau order:\n- Produk: {$title}\n- Link: /p/{$slug}";
    }

    function build_telegram_url(?string $target, string $message): string
    {
        $base = normalize_telegram_target($target);
        if ($base === '') {
            return '#';
        }
        return $base . '?text=' . rawurlencode($message);
    }

    function base_path_from_request(): string
    {
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $scriptName = rtrim($scriptName, '/');
        return $scriptName === '' ? '' : $scriptName;
    }

    function request_path(): string
    {
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path_from_request();

        if ($base !== '' && str_starts_with($uriPath, $base)) {
            $uriPath = substr($uriPath, strlen($base));
        }

        $uriPath = '/' . ltrim($uriPath, '/');
        return $uriPath === '//' ? '/' : $uriPath;
    }

    function app_url(string $path = ''): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = base_path_from_request();
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            $path = '';
        }
        return $scheme . '://' . $host . $base . $path;
    }

    function local_url(string $path = '/'): string
    {
        $base = base_path_from_request();
        $path = '/' . ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }
        return $base . $path;
    }

    function qv(string $key, ?string $default = null): ?string
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        $value = trim((string) $_GET[$key]);
        return $value === '' ? $default : $value;
    }

    function parse_positive_int(mixed $value, int $fallback = 1): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed === false || $parsed < 1) {
            return $fallback;
        }
        return (int) $parsed;
    }

    function normalize_catalog_sort(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return $value === 'oldest' ? 'oldest' : 'newest';
    }

    function normalize_badge_status(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $allowed = ['none', 'hot', 'ready', 'limited', 'new'];
        return in_array($value, $allowed, true) ? $value : 'none';
    }

    function parse_checkbox(mixed $value): bool
    {
        return in_array($value, ['on', 'true', '1', 1, true], true);
    }

    function parse_upload_datetime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    function build_catalog_url(array $params): string
    {
        $query = [];

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $query['q'] = $q;
        }

        $category = trim((string) ($params['category'] ?? ''));
        if ($category !== '') {
            $query['category'] = $category;
        }

        $sort = normalize_catalog_sort((string) ($params['sort'] ?? 'newest'));
        if ($sort !== 'newest') {
            $query['sort'] = $sort;
        }

        $page = (int) ($params['page'] ?? 1);
        if ($page > 1) {
            $query['page'] = $page;
        }

        if ($query === []) {
            return local_url('/katalog');
        }

        return local_url('/katalog?' . http_build_query($query));
    }

    function render_view(string $template, array $data = [], string $layout = 'public'): void
    {
        $templateFile = __DIR__ . '/views/' . $template . '.php';
        $layoutFile = __DIR__ . '/views/' . $layout . '_layout.php';

        if (!is_file($templateFile) || !is_file($layoutFile)) {
            http_response_code(500);
            echo 'Template tidak ditemukan.';
            return;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $templateFile;
        $content = ob_get_clean();

        include $layoutFile;
    }

    function extract_keywords(string $text): array
    {
        $stop = [
            'dan', 'atau', 'untuk', 'yang', 'dengan', 'the', 'and', 'for', 'from',
            'via', 'paket', 'produk', 'akun', 'ini', 'itu', 'dalam'
        ];

        $words = preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? '')) ?: [];
        $result = [];

        foreach ($words as $w) {
            $w = trim($w);
            if ($w === '' || strlen($w) < 3 || in_array($w, $stop, true)) {
                continue;
            }
            $result[$w] = true;
            if (count($result) >= 8) {
                break;
            }
        }

        return array_keys($result);
    }

    function related_score(array $source, array $candidate, array $sourceKeywords): int
    {
        $candidateKeywords = extract_keywords((string) ($candidate['title'] ?? ''));
        $candidateMap = array_flip($candidateKeywords);
        $overlap = 0;
        foreach ($sourceKeywords as $kw) {
            if (isset($candidateMap[$kw])) {
                $overlap++;
            }
        }

        $sameCategory = (int) ($source['category_id'] ?? 0) === (int) ($candidate['category_id'] ?? 0);
        $recency = 0;
        $candidateCreated = strtotime((string) ($candidate['createdAt'] ?? ''));
        if ($candidateCreated !== false) {
            $days = (int) floor((time() - $candidateCreated) / 86400);
            $recency = max(0, 10 - $days);
        }

        return ($sameCategory ? 40 : 0) + ($overlap * 12) + $recency;
    }

    function attach_product_meta(array $row): array
    {
        $row['is_new'] = is_new_product($row['createdAt'] ?? null);
        $badge = badge_meta((string) ($row['badge_status'] ?? 'none'), (bool) $row['is_new']);
        $row['badge_label'] = $badge['label'] ?? '';
        $row['badge_class'] = $badge['class'] ?? '';
        $row['tg_url'] = build_telegram_url($row['whatsapp_number'] ?? null, build_product_message($row));
        $row['tg_track_url'] = local_url('/go/tg/' . ($row['slug'] ?? ''));
        return $row;
    }

    function format_datetime_local(?string $value): string
    {
        if (!$value) {
            return '';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }
        return date('Y-m-d\TH:i', $ts);
    }

    function icon_svg(string $name, int $size = 16, string $class = '', float $strokeWidth = 1.9): string
    {
        $size = max(10, $size);
        $sw = $strokeWidth;
        $classAttr = $class === '' ? 'icon-svg' : 'icon-svg ' . $class;
        $head = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . e((string) $sw) . '" width="' . e((string) $size) . '" height="' . e((string) $size) . '" class="' . e($classAttr) . '">';
        $tail = '</svg>';

        return match ($name) {
            'home' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="m3 10.5 9-7 9 7V21a1 1 0 0 1-1 1h-5v-7h-6v7H4a1 1 0 0 1-1-1z"/>' . $tail,
            'grid' => $head . '<rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/>' . $tail,
            'admin' => $head . '<circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/>' . $tail,
            'store' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9h18l-1 10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 9V6a5 5 0 0 1 10 0v3"/>' . $tail,
            'search' => $head . '<circle cx="11" cy="11" r="7"/><path stroke-linecap="round" stroke-linejoin="round" d="m20 20-3.5-3.5"/>' . $tail,
            'reset' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 4v5h5"/>' . $tail,
            'category' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M20.5 12.5 12.5 20.5a2 2 0 0 1-2.8 0l-6.2-6.2a2 2 0 0 1 0-2.8l8-8h6a2 2 0 0 1 2 2z"/><circle cx="16" cy="8" r="1"/>' . $tail,
            'products' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8.5 12 4l9 4.5-9 4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 8.5V16l9 4 9-4V8.5"/>' . $tail,
            'detail' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7S2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>' . $tail,
            'telegram' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M21 4 3 11.5l6.5 2.3L12 20l9-16z"/><path stroke-linecap="round" stroke-linejoin="round" d="m9.5 13.8 6.2-5.7"/>' . $tail,
            'share' => $head . '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="m8.6 10.8 6.8-3.6m-6.8 6.1 6.8 3.6"/>' . $tail,
            'copy' => $head . '<rect x="9" y="9" width="12" height="12" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>' . $tail,
            'back' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/>' . $tail,
            'sort' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M10 17h4"/>' . $tail,
            'spark' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.6 3.7L17 8.3l-3.4 1.6L12 13.5l-1.6-3.6L7 8.3l3.4-1.6z"/><path stroke-linecap="round" stroke-linejoin="round" d="m19 14 1 2.3L22 17l-2 .7-1 2.3-1-2.3-2-.7 2-.7z"/>' . $tail,
            'arrow-right' => $head . '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>' . $tail,
            default => $head . '<circle cx="12" cy="12" r="8"/>' . $tail,
        };
    }
}

