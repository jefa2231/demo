<!doctype html>
<html lang="id">
<head>
  <?php
  $metaDesc = $metaDescription ?? ('Katalog produk ' . cfg('app_name'));
  $resolvedMetaUrl = trim((string) ($metaUrl ?? ''));
  $resolvedOgImageUrl = trim((string) ($ogImageUrl ?? ''));
  ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e(($title ?? 'JeStore') . ' | ' . cfg('app_name')) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>" />
  <meta property="og:title" content="<?= e(($title ?? 'JeStore') . ' | ' . cfg('app_name')) ?>" />
  <meta property="og:description" content="<?= e($metaDesc) ?>" />
  <meta property="og:type" content="website" />
  <?php if ($resolvedMetaUrl !== ''): ?>
  <meta property="og:url" content="<?= e($resolvedMetaUrl) ?>" />
  <link rel="canonical" href="<?= e($resolvedMetaUrl) ?>" />
  <?php endif; ?>
  <?php if ($resolvedOgImageUrl !== ''): ?>
  <meta property="og:image" content="<?= e($resolvedOgImageUrl) ?>" />
  <meta property="twitter:card" content="summary_large_image" />
  <meta property="twitter:image" content="<?= e($resolvedOgImageUrl) ?>" />
  <?php else: ?>
  <meta property="twitter:card" content="summary" />
  <?php endif; ?>
  <meta property="twitter:title" content="<?= e(($title ?? 'JeStore') . ' | ' . cfg('app_name')) ?>" />
  <meta property="twitter:description" content="<?= e($metaDesc) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Clash+Display:wght@600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(local_url('/css/app.css')) ?>?v=<?= filemtime(__DIR__ . '/../../public/css/app.css') ?: time() ?>" />
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
  <?php
  $cp = $currentPath ?? '/';
  $isCatalog = str_starts_with($cp, '/katalog') || str_starts_with($cp, '/p/') || str_starts_with($cp, '/c/');
  $pbClass = str_starts_with($cp, '/p/') ? 'pb-52' : 'pb-28';
  ?>
  <div class="ambient-wrap pointer-events-none fixed inset-0">
    <span class="ambient-orb ambient-orb-cyan"></span>
    <span class="ambient-orb ambient-orb-emerald"></span>
    <span class="ambient-orb ambient-orb-indigo"></span>
  </div>
  <div class="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_15%_5%,rgba(14,165,233,0.12),transparent_35%),radial-gradient(circle_at_85%_20%,rgba(16,185,129,0.10),transparent_40%)]"></div>

  <div class="mx-auto max-w-[480px] px-4 py-4 <?= e($pbClass) ?> space-y-4">
    <header class="topbar-shell relative z-10 rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 backdrop-blur" data-reveal data-reveal-delay="30">
      <div class="flex items-center justify-between">
        <a href="<?= e(local_url('/')) ?>" class="inline-flex items-center gap-2 font-title text-lg font-semibold tracking-tight" data-ripple>
          <span class="icon-pill"><?= icon_svg('store', 14) ?></span>
          <span><?= e((string) cfg('app_name')) ?></span>
        </a>
        <a href="<?= e(local_url('/admin/login')) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] font-semibold text-slate-200 hover:bg-white/10" data-ripple>
          <?= icon_svg('admin', 14) ?>
          <span>Admin</span>
        </a>
      </div>
    </header>

    <main class="relative z-10"><?= $content ?></main>
  </div>

  <nav class="nav-shell fixed bottom-0 left-0 right-0 z-50 border-t border-white/10 bg-slate-950/95 backdrop-blur">
    <div class="mx-auto max-w-[480px] grid h-16 grid-cols-3 px-2">
      <a href="<?= e(local_url('/')) ?>" class="nav-item <?= $cp === '/' ? 'nav-item-active' : '' ?>" data-ripple>
        <?= icon_svg('home', 16) ?>
        <span>Beranda</span>
      </a>
      <a href="<?= e(local_url('/katalog')) ?>" class="nav-item <?= $isCatalog ? 'nav-item-active' : '' ?>" data-ripple>
        <?= icon_svg('grid', 16) ?>
        <span>Katalog</span>
      </a>
      <a href="<?= e(local_url('/admin/login')) ?>" class="nav-item" data-ripple>
        <?= icon_svg('admin', 16) ?>
        <span>Admin</span>
      </a>
    </div>
  </nav>

  <script src="<?= e(local_url('/js/app.js')) ?>?v=<?= filemtime(__DIR__ . '/../../public/js/app.js') ?: time() ?>"></script>
</body>
</html>

