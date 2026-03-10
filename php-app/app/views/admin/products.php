<?php
$badgeMap = [
  'hot' => ['label' => 'Hot', 'class' => 'badge-hot'],
  'ready' => ['label' => 'Ready', 'class' => 'badge-ready'],
  'limited' => ['label' => 'Limited', 'class' => 'badge-limited'],
  'new' => ['label' => 'New', 'class' => 'badge-new'],
];
$nowTs = time();
?>

<section class="space-y-3">
  <div class="flex items-center justify-between gap-3">
    <h1 class="text-base font-semibold">Produk</h1>
    <a href="<?= e(local_url('/admin/products/new')) ?>" class="rounded-xl bg-emerald-500 text-black px-3 py-2 text-sm font-semibold hover:bg-emerald-400">Tambah</a>
  </div>

  <?php if (!empty($errorMessage)): ?>
  <p class="text-sm rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-red-300"><?= e((string) $errorMessage) ?></p>
  <?php endif; ?>
  <?php if (!empty($successMessage)): ?>
  <p class="text-sm rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-emerald-300"><?= e((string) $successMessage) ?></p>
  <?php endif; ?>

  <?php if (empty($products)): ?>
  <div class="rounded-2xl border border-white/10 bg-slate-900 p-4 text-sm text-slate-300">Belum ada produk.</div>
  <?php else: ?>
  <div class="space-y-3">
    <?php foreach ($products as $product): ?>
    <?php
      $badge = $badgeMap[strtolower((string) ($product['badge_status'] ?? ''))] ?? null;
      $views = (int) ($product['view_count'] ?? 0);
      $clicks = (int) ($product['telegram_click_count'] ?? 0);
      $ctr = $views > 0 ? ($clicks / $views) * 100 : 0;
      $publishTs = !empty($product['publish_at']) ? strtotime((string) $product['publish_at']) : false;
      $isScheduled = $publishTs !== false && $publishTs > $nowTs;
      $imagePath = (string) ($product['image_path'] ?? '');
    ?>
    <article class="rounded-2xl border border-white/10 bg-slate-900 p-3 flex gap-3">
      <div class="w-20 aspect-[9/16] rounded-lg bg-black/30 overflow-hidden shrink-0">
        <?php if ($imagePath !== ''): ?>
        <img src="<?= e($imagePath) ?>" alt="<?= e((string) ($product['title'] ?? 'Produk')) ?>" class="h-full w-full object-contain bg-black" />
        <?php endif; ?>
      </div>
      <div class="flex-1 space-y-1">
        <div class="flex items-center gap-2">
          <h2 class="text-sm font-semibold"><?= e((string) ($product['title'] ?? 'Produk')) ?></h2>
          <?php if ($badge): ?>
          <span class="badge-chip <?= e((string) $badge['class']) ?>"><?= e((string) $badge['label']) ?></span>
          <?php endif; ?>
        </div>

        <p class="text-xs text-slate-300"><?= e((string) ($product['category_name'] ?? 'Tanpa kategori')) ?></p>
        <p class="text-xs text-slate-300">Views <?= $views ?> ? Klik TG <?= $clicks ?> ? CTR <?= number_format($ctr, 1) ?>%</p>
        <p class="text-xs <?= $isScheduled ? 'text-cyan-300' : 'text-slate-400' ?>">
          <?= $isScheduled ? ('Terjadwal ' . date('d M Y H:i', $publishTs)) : 'Publish: langsung / sudah tayang' ?>
        </p>
        <p class="text-xs <?= (int) ($product['is_active'] ?? 0) === 1 ? 'text-emerald-300' : 'text-amber-300' ?>">
          <?= (int) ($product['is_active'] ?? 0) === 1 ? 'Aktif' : 'Nonaktif' ?> ? <?= (int) ($product['image_total'] ?? 0) ?> gambar
        </p>

        <div class="flex flex-wrap gap-2 pt-1">
          <a href="<?= e(local_url('/admin/products/' . (int) $product['id'] . '/edit')) ?>" class="rounded-lg bg-white/10 px-2 py-1 text-xs">Edit</a>
          <form action="<?= e(local_url('/admin/products/' . (int) $product['id'] . '/toggle')) ?>" method="post">
            <button type="submit" class="rounded-lg bg-white/10 px-2 py-1 text-xs">Toggle</button>
          </form>
          <form action="<?= e(local_url('/admin/products/' . (int) $product['id'] . '/delete')) ?>" method="post" data-confirm-delete="Hapus produk ini?">
            <button type="submit" class="rounded-lg bg-red-500/20 text-red-200 px-2 py-1 text-xs">Hapus</button>
          </form>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

