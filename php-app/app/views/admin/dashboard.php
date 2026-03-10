<?php
$badgeMap = [
  'hot' => ['label' => 'Hot', 'class' => 'badge-hot'],
  'ready' => ['label' => 'Ready', 'class' => 'badge-ready'],
  'limited' => ['label' => 'Limited', 'class' => 'badge-limited'],
  'new' => ['label' => 'New', 'class' => 'badge-new'],
];
?>

<section class="grid grid-cols-1 sm:grid-cols-4 gap-3">
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">Total Produk</p><p class="text-2xl font-bold"><?= (int) ($stats['productsCount'] ?? 0) ?></p></article>
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">Produk Aktif</p><p class="text-2xl font-bold"><?= (int) ($stats['activeProductsCount'] ?? 0) ?></p></article>
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">Terjadwal Publish</p><p class="text-2xl font-bold text-cyan-300"><?= (int) ($stats['scheduledProductsCount'] ?? 0) ?></p></article>
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">Total Kategori</p><p class="text-2xl font-bold"><?= (int) ($stats['categoriesCount'] ?? 0) ?></p></article>
</section>

<section class="grid grid-cols-1 sm:grid-cols-3 gap-3">
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">Total View Produk</p><p class="text-2xl font-bold"><?= (int) ($stats['totalViews'] ?? 0) ?></p></article>
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">Total Klik Telegram</p><p class="text-2xl font-bold text-emerald-300"><?= (int) ($stats['totalClicks'] ?? 0) ?></p></article>
  <article class="rounded-2xl border border-white/10 bg-slate-900 p-4"><p class="text-xs text-slate-300">CTR Keseluruhan</p><p class="text-2xl font-bold text-cyan-300"><?= number_format((float) ($stats['totalCtr'] ?? 0), 1) ?>%</p></article>
</section>

<section class="rounded-2xl border border-white/10 bg-slate-900 p-4 space-y-2">
  <div class="flex items-center justify-between">
    <h2 class="text-sm font-semibold">Top Produk Berdasarkan Klik Telegram</h2>
    <a href="<?= e(local_url('/admin/products')) ?>" class="text-xs text-emerald-300">Lihat semua</a>
  </div>

  <?php if (empty($topProducts)): ?>
  <p class="text-sm text-slate-300">Belum ada data analytics produk.</p>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-400 text-xs">
          <th class="py-2 pr-2">#</th>
          <th class="py-2 pr-2">Produk</th>
          <th class="py-2 pr-2">Views</th>
          <th class="py-2 pr-2">Klik TG</th>
          <th class="py-2 pr-2">CTR</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topProducts as $item): ?>
        <?php
          $b = $badgeMap[strtolower((string) ($item['badge_status'] ?? ''))] ?? null;
          $isScheduled = !empty($item['publish_at']) && strtotime((string) $item['publish_at']) > time();
        ?>
        <tr class="border-t border-white/10">
          <td class="py-2 pr-2 text-slate-300"><?= (int) ($item['rank'] ?? 0) ?></td>
          <td class="py-2 pr-2">
            <a href="<?= e(local_url('/p/' . (string) ($item['slug'] ?? ''))) ?>" target="_blank" rel="noopener noreferrer" class="font-semibold hover:text-emerald-300"><?= e((string) ($item['title'] ?? 'Produk')) ?></a>
            <div class="mt-1 flex items-center gap-1.5">
              <?php if ($b): ?><span class="badge-chip <?= e((string) $b['class']) ?>"><?= e((string) $b['label']) ?></span><?php endif; ?>
              <?php if ($isScheduled): ?><span class="text-[10px] text-cyan-300">Scheduled</span><?php endif; ?>
            </div>
          </td>
          <td class="py-2 pr-2 text-slate-200"><?= (int) ($item['views'] ?? 0) ?></td>
          <td class="py-2 pr-2 text-emerald-300"><?= (int) ($item['clicks'] ?? 0) ?></td>
          <td class="py-2 pr-2 text-cyan-300"><?= number_format((float) ($item['ctr'] ?? 0), 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<section class="rounded-2xl border border-white/10 bg-slate-900 p-4 space-y-2">
  <h2 class="text-sm font-semibold">Quick Actions</h2>
  <div class="flex gap-2">
    <a href="<?= e(local_url('/admin/products/new')) ?>" class="rounded-xl bg-emerald-500 text-black px-3 py-2 text-sm font-semibold hover:bg-emerald-400">Tambah Produk</a>
    <a href="<?= e(local_url('/admin/categories')) ?>" class="rounded-xl bg-white/10 text-slate-100 px-3 py-2 text-sm font-semibold hover:bg-white/20">Kelola Kategori</a>
  </div>
</section>

