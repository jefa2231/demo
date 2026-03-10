<?php
$imageList = $productImages ?? [];
$imageCount = count($imageList);
$descriptionLines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($product['description'] ?? '')) ?: [])));
$featureList = $descriptionLines !== []
  ? array_slice($descriptionLines, 0, 5)
  : [
      'Preview langsung dari screenshot produk asli.',
      'Tampilan responsif dan nyaman di mobile.',
      'Foto full frame tanpa terpotong.',
      'Checkout cepat via Telegram.'
    ];
$safeTgUrl = trim((string) ($tgTrackUrl ?? ($product['tg_track_url'] ?? '#')));
?>

<section class="space-y-4">
  <article class="surface rounded-3xl p-3.5" data-reveal data-reveal-delay="50">
    <div class="flex items-center justify-between">
      <a href="<?= e(local_url('/katalog')) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-white/10" data-ripple>
        <?= icon_svg('back', 13) ?>
        <span>Katalog</span>
      </a>
      <div class="flex items-center gap-1.5">
        <button type="button" data-share-product data-share-url="<?= e((string) ($shareUrl ?? app_url('/p/' . (string) ($product['slug'] ?? '')))) ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-slate-200 hover:bg-white/10" data-ripple>
          <?= icon_svg('share', 13) ?>
        </button>
        <button type="button" data-copy-link data-copy-url="<?= e((string) ($shareUrl ?? app_url('/p/' . (string) ($product['slug'] ?? '')))) ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-white/15 bg-white/5 text-slate-200 hover:bg-white/10" data-ripple>
          <?= icon_svg('copy', 13) ?>
        </button>
      </div>
    </div>

    <div class="mt-3 rounded-2xl border border-cyan-300/25 bg-[radial-gradient(circle_at_10%_10%,rgba(34,211,238,0.15),transparent_35%),linear-gradient(145deg,rgba(15,23,42,0.98),rgba(2,6,23,0.92))] p-4">
      <p class="text-[10px] uppercase tracking-[0.2em] text-cyan-200/90">Preview Produk</p>
      <div class="mt-2 flex items-center gap-2">
        <h1 class="text-[1.32rem] font-semibold leading-tight text-slate-100"><?= e((string) ($product['title'] ?? 'Produk')) ?></h1>
        <?php if (!empty($product['badge_label'])): ?>
        <span class="badge-chip <?= e((string) $product['badge_class']) ?>"><?= e((string) $product['badge_label']) ?></span>
        <?php endif; ?>
      </div>

      <div class="mt-3 grid grid-cols-3 gap-2">
        <div class="rounded-xl border border-white/15 bg-white/5 p-2.5">
          <p class="text-[10px] text-slate-400">Kategori</p>
          <p class="mt-1 text-xs font-semibold text-slate-100"><?= e((string) ($product['category_name'] ?? 'Umum')) ?></p>
        </div>
        <div class="rounded-xl border border-white/15 bg-white/5 p-2.5">
          <p class="text-[10px] text-slate-400">Preview</p>
          <p class="mt-1 text-xs font-semibold text-slate-100"><?= (int) $imageCount ?> gambar</p>
        </div>
        <div class="rounded-xl border border-emerald-300/25 bg-emerald-500/10 p-2.5">
          <p class="text-[10px] text-emerald-200/90">Aksi</p>
          <p class="mt-1 text-xs font-semibold text-emerald-100">Order cepat</p>
        </div>
      </div>

      <ul class="mt-3 space-y-1.5">
        <?php foreach ($featureList as $feature): ?>
        <li class="flex items-start gap-2 text-sm text-slate-200">
          <span class="mt-[1px] text-cyan-300"><?= icon_svg('spark', 12) ?></span>
          <span><?= e((string) $feature) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </article>

  <article class="surface rounded-3xl p-3.5 space-y-3" data-product-carousel-root data-reveal data-reveal-delay="95">
    <div class="flex items-center justify-between">
      <h2 class="section-title text-sm">
        <?= icon_svg('products', 14) ?>
        <span>Galeri Preview</span>
      </h2>
      <span data-carousel-counter class="text-[11px] font-semibold text-slate-300"><?= $imageCount > 1 ? ('1 / ' . $imageCount) : ($imageCount . ' foto') ?></span>
    </div>

    <div class="relative">
      <div class="carousel-viewport overflow-hidden rounded-2xl border border-white/10 bg-black/35" data-product-carousel>
        <div class="carousel-track flex transition-transform duration-300 ease-out" data-carousel-track>
          <?php if ($imageCount > 0): ?>
            <?php foreach ($imageList as $idx => $img): ?>
            <div class="w-full shrink-0" data-carousel-slide data-index="<?= (int) $idx ?>">
              <div class="flex min-h-[420px] items-center justify-center bg-slate-950/70 p-2.5 sm:min-h-[500px]">
                <img
                  src="<?= e((string) ($img['image_path'] ?? '')) ?>"
                  alt="<?= e((string) ($product['title'] ?? 'Produk')) ?> - <?= (int) ($idx + 1) ?>"
                  class="max-h-[72vh] w-full cursor-zoom-in object-contain"
                  data-lightbox-open
                  data-index="<?= (int) $idx ?>"
                />
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
          <div class="w-full shrink-0" data-carousel-slide data-index="0">
            <div class="flex min-h-[420px] items-center justify-center bg-slate-950/70 text-sm text-slate-400">Belum ada gambar produk.</div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($imageCount > 1): ?>
      <button type="button" data-carousel-prev class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full border border-white/20 bg-slate-950/80 p-2 text-slate-100 backdrop-blur transition hover:bg-slate-900">
        <?= icon_svg('back', 14) ?>
      </button>
      <button type="button" data-carousel-next class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full border border-white/20 bg-slate-950/80 p-2 text-slate-100 backdrop-blur transition hover:bg-slate-900">
        <?= icon_svg('arrow-right', 14) ?>
      </button>
      <?php endif; ?>
    </div>

    <?php if ($imageCount > 1): ?>
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" data-carousel-thumbs>
      <?php foreach ($imageList as $idx => $img): ?>
      <button
        type="button"
        data-carousel-thumb
        data-lightbox-open
        data-index="<?= (int) $idx ?>"
        class="carousel-thumb overflow-hidden rounded-xl border border-white/15 bg-slate-900/85 text-left transition hover:border-emerald-300/45"
      >
        <div class="aspect-[9/16] border-b border-white/10 bg-black/35 p-1.5">
          <img src="<?= e((string) ($img['image_path'] ?? '')) ?>" alt="thumb <?= (int) ($idx + 1) ?>" class="h-full w-full object-contain" />
        </div>
        <div class="px-2 py-1.5 text-[11px] font-semibold text-slate-200">Preview <?= (int) ($idx + 1) ?></div>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p class="text-[11px] text-slate-400"><?= $imageCount > 1 ? 'Pakai tombol kanan/kiri, swipe, atau klik preview di bawah.' : 'Foto ditampilkan full tanpa crop.' ?></p>
  </article>

  <article class="surface rounded-3xl p-4" data-reveal data-reveal-delay="130">
    <div class="flex items-center justify-between gap-2">
      <h2 class="section-title text-sm">
        <?= icon_svg('detail', 14) ?>
        <span>Deskripsi</span>
      </h2>
      <?php if (!empty($product['category_slug'])): ?>
      <a href="<?= e(build_catalog_url(['category' => (string) $product['category_slug']])) ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-300 hover:text-emerald-200" data-ripple>
        <span>Produk serupa</span>
        <?= icon_svg('arrow-right', 12) ?>
      </a>
      <?php endif; ?>
    </div>
    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-300"><?= e($descriptionLines !== [] ? implode("\n", $descriptionLines) : 'Belum ada deskripsi tambahan untuk produk ini.') ?></p>
  </article>

  <?php if (!empty($relatedProducts)): ?>
  <section class="space-y-2" data-reveal data-reveal-delay="165">
    <h2 class="section-title text-sm">
      <?= icon_svg('spark', 14) ?>
      <span>Produk Serupa Pintar</span>
    </h2>
    <div class="grid grid-cols-2 gap-2">
      <?php foreach ($relatedProducts as $idx => $item): ?>
      <a href="<?= e(local_url('/p/' . (string) ($item['slug'] ?? ''))) ?>" class="product-card overflow-hidden rounded-xl border border-white/10 bg-slate-900/85" data-reveal data-reveal-delay="<?= (int) (($idx % 8) * 45) ?>">
        <div class="aspect-[9/16] bg-black/30 p-1.5">
          <?php if (!empty($item['image_path'])): ?>
          <img src="<?= e((string) $item['image_path']) ?>" alt="<?= e((string) ($item['title'] ?? 'Produk')) ?>" class="h-full w-full object-contain" />
          <?php else: ?>
          <div class="flex h-full w-full items-center justify-center text-xs text-slate-400">No Image</div>
          <?php endif; ?>
        </div>
        <div class="space-y-1.5 p-2">
          <?php if (!empty($item['badge_label'])): ?>
          <span class="badge-chip <?= e((string) $item['badge_class']) ?>"><?= e((string) $item['badge_label']) ?></span>
          <?php endif; ?>
          <p class="h-8 overflow-hidden text-[11px] font-semibold leading-snug text-slate-100"><?= e((string) ($item['title'] ?? 'Produk')) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</section>

<div class="lightbox-root" data-product-lightbox aria-hidden="true">
  <button type="button" class="lightbox-backdrop" data-lightbox-close aria-label="Tutup preview fullscreen"></button>
  <div class="lightbox-shell">
    <button type="button" class="lightbox-close-btn" data-lightbox-close>Tutup</button>
    <button type="button" class="lightbox-nav lightbox-nav-left" data-lightbox-prev aria-label="Preview sebelumnya">
      <?= icon_svg('back', 16) ?>
    </button>
    <figure class="lightbox-frame">
      <img src="" alt="Preview fullscreen" class="lightbox-image" data-lightbox-image />
      <figcaption class="lightbox-caption" data-lightbox-counter>1 / 1</figcaption>
    </figure>
    <button type="button" class="lightbox-nav lightbox-nav-right" data-lightbox-next aria-label="Preview berikutnya">
      <?= icon_svg('arrow-right', 16) ?>
    </button>
  </div>
</div>

<div class="cta-dock fixed bottom-[4.35rem] left-0 right-0 z-[55]">
  <div class="mx-auto max-w-[480px] px-4">
    <a href="<?= e($safeTgUrl !== '' ? $safeTgUrl : '#') ?>" target="_blank" rel="noopener noreferrer" class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-cyan-300/40 bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-300 px-4 py-3 text-sm font-semibold text-slate-950 shadow-card transition hover:brightness-110 active:scale-[0.99]" data-ripple>
      <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-950/12"><?= icon_svg('telegram', 14, '', 2) ?></span>
      <span>Order Sekarang via Telegram</span>
      <span class="transition group-hover:translate-x-0.5"><?= icon_svg('arrow-right', 14, '', 2) ?></span>
    </a>
  </div>
</div>

