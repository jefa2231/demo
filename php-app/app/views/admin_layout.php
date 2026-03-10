<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e(($title ?? 'Admin') . ' | ' . cfg('app_name')) ?></title>
  <link rel="stylesheet" href="<?= e(local_url('/css/app.css')) ?>?v=<?= filemtime(__DIR__ . '/../../public/css/app.css') ?: time() ?>" />
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
  <?php $admin = $currentAdmin ?? admin_user(); ?>
  <?php $cp = $currentPath ?? '/admin'; ?>
  <div class="mx-auto max-w-6xl px-4 py-4 space-y-4">
    <header class="rounded-2xl border border-white/10 bg-slate-900/80 p-3 flex items-center justify-between gap-3">
      <div>
        <p class="text-sm font-semibold">Admin Panel</p>
        <?php if ($admin): ?>
        <p class="text-xs text-slate-300">@<?= e((string) ($admin['username'] ?? 'admin')) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($admin): ?>
      <nav class="flex items-center gap-2 text-xs">
        <a class="rounded-lg px-2 py-1 <?= $cp === '/admin' ? 'bg-emerald-500 text-black' : 'bg-white/10 text-slate-200' ?>" href="<?= e(local_url('/admin')) ?>">Dashboard</a>
        <a class="rounded-lg px-2 py-1 <?= str_starts_with($cp, '/admin/products') ? 'bg-emerald-500 text-black' : 'bg-white/10 text-slate-200' ?>" href="<?= e(local_url('/admin/products')) ?>">Produk</a>
        <a class="rounded-lg px-2 py-1 <?= str_starts_with($cp, '/admin/categories') ? 'bg-emerald-500 text-black' : 'bg-white/10 text-slate-200' ?>" href="<?= e(local_url('/admin/categories')) ?>">Kategori</a>
        <form action="<?= e(local_url('/admin/logout')) ?>" method="post">
          <button class="rounded-lg px-2 py-1 bg-white/10 text-slate-200 hover:bg-white/20" type="submit">Logout</button>
        </form>
      </nav>
      <?php endif; ?>
    </header>

    <?= $content ?>
  </div>

  <script src="<?= e(local_url('/js/app.js')) ?>?v=<?= filemtime(__DIR__ . '/../../public/js/app.js') ?: time() ?>"></script>
</body>
</html>

