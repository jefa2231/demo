<?php
$selectedSlug = (string) ($selectedCategorySlug ?? '');
?>
<section class="space-y-4" data-catalog-page>
  <article class="surface rounded-3xl p-4" data-reveal data-reveal-delay="45">
    <div class="flex items-start justify-between gap-2">
      <div>
        <h1 class="section-title text-base">
          <?= icon_svg('grid', 16) ?>
          <span>Katalog Produk</span>
        </h1>
        <p class="mt-1 text-sm text-slate-300">Cari, filter, dan urutkan produk sesuai kebutuhan.</p>
      </div>
      <a href="<?= e(local_url('/katalog')) ?>" class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] font-semibold text-slate-200 hover:bg-white/10">
        <?= icon_svg('reset', 13) ?>
        <span>Reset</span>
      </a>
    </div>

    <form action="<?= e(local_url('/katalog')) ?>" method="get" data-live-search-form class="mt-3 space-y-2 rounded-2xl border border-white/10 bg-slate-900/50 p-2.5">
      <div class="flex gap-2">
        <label class="relative flex-1">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><?= icon_svg('search', 14) ?></span>
          <input
            type="text"
            name="q"
            value="<?= e((string) ($q ?? '')) ?>"
            placeholder="Cari produk"
            autocomplete="off"
            data-live-search
            class="w-full rounded-xl border border-white/10 bg-slate-950 py-2 pl-9 pr-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60"
          />
        </label>
        <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-emerald-500 px-3 py-2 text-sm font-semibold text-black hover:bg-emerald-400" data-ripple>
          <?= icon_svg('search', 14, '', 2) ?>
          <span>Cari</span>
        </button>
      </div>

      <?php if ($selectedSlug !== ''): ?>
      <input type="hidden" name="category" value="<?= e($selectedSlug) ?>" />
      <?php endif; ?>
      <input type="hidden" name="sort" value="<?= e((string) ($sort ?? 'newest')) ?>" />
    </form>
  </article>

  <div data-catalog-dynamic class="space-y-4">
    <section class="space-y-2" data-reveal data-reveal-delay="75">
      <div class="no-scrollbar flex gap-2 overflow-x-auto pb-1">
        <?php foreach (($sortLinks ?? []) as $item): ?>
        <a href="<?= e((string) ($item['url'] ?? '#')) ?>" class="chip <?= !empty($item['active']) ? 'chip-active' : '' ?>" data-ripple>
          <?= icon_svg('sort', 12) ?>
          <span><?= e((string) ($item['label'] ?? 'Sort')) ?></span>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="no-scrollbar flex gap-2 overflow-x-auto pb-1">
        <?php foreach (($categoryLinks ?? []) as $item): ?>
        <a href="<?= e((string) ($item['url'] ?? '#')) ?>" class="chip <?= !empty($item['active']) ? 'chip-active' : '' ?>" data-ripple>
          <?= icon_svg('category', 12) ?>
          <span><?= e((string) ($item['name'] ?? 'Kategori')) ?></span>
          <span class="text-slate-400">(<?= (int) ($item['total'] ?? 0) ?>)</span>
        </a>
        <?php endforeach; ?>
      </div>

      <p class="text-xs text-slate-400">
        Menampilkan <span class="font-semibold text-slate-200"><?= (int) count($products ?? []) ?></span> dari
        <span class="font-semibold text-slate-200"><?= (int) (($pagination['totalProducts'] ?? 0)) ?></span> produk.
      </p>
    </section>

    <?php if (empty($products)): ?>
    <div class="rounded-2xl bg-slate-900 border border-white/10 p-4 text-sm text-slate-300">Produk tidak ditemukan.</div>
    <?php else: ?>
    <div class="grid grid-cols-2 gap-3" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;">
      <?php foreach ($products as $idx => $product): ?>
      <article class="product-card rounded-2xl overflow-hidden border border-white/10 bg-slate-900/85" data-reveal data-reveal-delay="<?= (int) (($idx % 10) * 36) ?>">
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
          <h2 class="text-sm font-semibold leading-snug min-h-[2.4rem] overflow-hidden"><?= e((string) ($product['title'] ?? 'Produk')) ?></h2>
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

    <?php if ((int) ($pagination['totalPages'] ?? 1) > 1): ?>
    <nav class="mt-4 flex items-center justify-center gap-1.5" data-reveal data-reveal-delay="140">
      <?php if (!empty($pagination['hasPrev'])): ?>
      <a href="<?= e((string) ($pagination['prevUrl'] ?? '#')) ?>" class="page-btn">
        <?= icon_svg('back', 13) ?>
        <span>Prev</span>
      </a>
      <?php endif; ?>

      <?php foreach (($pagination['pages'] ?? []) as $p): ?>
      <a href="<?= e((string) ($p['url'] ?? '#')) ?>" class="page-btn <?= !empty($p['active']) ? 'page-btn-active' : '' ?>"><?= (int) ($p['page'] ?? 1) ?></a>
      <?php endforeach; ?>

      <?php if (!empty($pagination['hasNext'])): ?>
      <a href="<?= e((string) ($pagination['nextUrl'] ?? '#')) ?>" class="page-btn">
        <span>Next</span>
        <?= icon_svg('arrow-right', 13) ?>
      </a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

