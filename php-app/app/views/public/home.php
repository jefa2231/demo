<section class="space-y-5">
  <article class="surface rounded-3xl p-4" data-reveal data-reveal-delay="40">
    <p class="text-[11px] uppercase tracking-[0.16em] text-emerald-300">Store Catalog</p>
    <h1 class="mt-2 text-[1.45rem] font-semibold leading-tight">Tampilan produk yang rapi, ringan, dan siap order cepat.</h1>
    <p class="mt-2 text-sm text-slate-300 leading-relaxed">Klik produk, lihat detail foto portrait, lalu lanjut checkout cepat via Telegram.</p>
    <a href="<?= e(local_url('/katalog')) ?>" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-emerald-500 px-3.5 py-2 text-sm font-semibold text-black hover:bg-emerald-400 transition" data-ripple>
      <span>Lihat Katalog</span>
      <?= icon_svg('arrow-right', 14, '', 2) ?>
    </a>
  </article>

  <section class="space-y-2" data-reveal data-reveal-delay="80">
    <div class="flex items-center justify-between">
      <h2 class="section-title">
        <?= icon_svg('category', 15) ?>
        <span>Pilihan Kategori</span>
      </h2>
      <a href="<?= e(local_url('/katalog')) ?>" class="text-xs text-emerald-300 hover:text-emerald-200">Semua</a>
    </div>

    <div class="no-scrollbar flex gap-2 overflow-x-auto pb-1">
      <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $category): ?>
        <a href="<?= e(build_catalog_url(['category' => (string) ($category['slug'] ?? '')])) ?>" class="chip" data-ripple>
          <?= icon_svg('category', 13) ?>
          <span><?= e((string) ($category['name'] ?? 'Kategori')) ?></span>
          <span class="text-slate-400">(<?= (int) ($category['total'] ?? 0) ?>)</span>
        </a>
        <?php endforeach; ?>
      <?php else: ?>
      <p class="text-xs text-slate-400">Belum ada kategori aktif.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="space-y-2" data-reveal data-reveal-delay="120">
    <div class="flex items-center justify-between">
      <h2 class="section-title">
        <?= icon_svg('products', 15) ?>
        <span>Semua Produk</span>
      </h2>
      <a href="<?= e(local_url('/katalog')) ?>" class="text-xs text-emerald-300 hover:text-emerald-200">Explore</a>
    </div>

    <?php if (empty($products)): ?>
    <div class="rounded-2xl bg-slate-900 border border-white/10 p-4 text-sm text-slate-300">Produk belum tersedia.</div>
    <?php else: ?>
    <div class="grid grid-cols-2 gap-3" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;">
      <?php foreach ($products as $idx => $product): ?>
      <article class="product-card rounded-2xl overflow-hidden border border-white/10 bg-slate-900/85" data-reveal data-reveal-delay="<?= (int) (($idx % 8) * 45) ?>">
        <a href="<?= e(local_url('/p/' . (string) ($product['slug'] ?? ''))) ?>" class="relative block aspect-[9/16] bg-black/20">
          <?php if (!empty($product['badge_label'])): ?>
          <span class="badge-chip <?= e((string) $product['badge_class']) ?> absolute left-2 top-2 z-10"><?= e((string) $product['badge_label']) ?></span>
          <?php endif; ?>
          <?php if (!empty($product['image_path'])): ?>
          <img src="<?= e((string) $product['image_path']) ?>" alt="<?= e((string) ($product['title'] ?? 'Produk')) ?>" loading="lazy" decoding="async" class="h-full w-full object-contain bg-black" />
          <?php else: ?>
          <div class="h-full w-full flex items-center justify-center text-xs text-slate-400">No Image</div>
          <?php endif; ?>
        </a>

        <div class="p-2.5 space-y-1.5">
          <h3 class="text-sm font-semibold leading-snug min-h-[2.4rem] overflow-hidden"><?= e((string) ($product['title'] ?? 'Produk')) ?></h3>
          <div class="grid grid-cols-2 gap-1.5">
            <a href="<?= e(local_url('/p/' . (string) ($product['slug'] ?? ''))) ?>" class="action-btn action-btn-main" data-ripple>
              <?= icon_svg('detail', 13) ?>
              <span>Detail</span>
            </a>
            <a href="<?= e((string) ($product['tg_track_url'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer" class="action-btn action-btn-ghost" data-ripple>
              <?= icon_svg('telegram', 13) ?>
              <span>TG</span>
            </a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</section>

