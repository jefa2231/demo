<?php
$mode = (string) ($mode ?? 'create');
$isCreate = $mode === 'create';
$product = $product ?? [];
$publishAtValue = format_datetime_local($product['publish_at'] ?? null);
$selectedBadge = (string) ($product['badge_status'] ?? 'none');
$images = $product['images'] ?? [];
?>

<section class="space-y-3">
  <div class="flex items-center justify-between">
    <h1 class="text-base font-semibold"><?= $isCreate ? 'Tambah Produk' : 'Edit Produk' ?></h1>
    <a href="<?= e(local_url('/admin/products')) ?>" class="text-xs text-slate-300">Kembali</a>
  </div>

  <?php if (!empty($errorMessage)): ?>
  <p class="text-sm rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-red-300"><?= e((string) $errorMessage) ?></p>
  <?php endif; ?>
  <?php if (!empty($successMessage)): ?>
  <p class="text-sm rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-emerald-300"><?= e((string) $successMessage) ?></p>
  <?php endif; ?>

  <form action="<?= e($isCreate ? local_url('/admin/products') : local_url('/admin/products/' . (int) ($product['id'] ?? 0))) ?>" method="post" enctype="multipart/form-data" class="rounded-2xl border border-white/10 bg-slate-900 p-4 space-y-3">
    <div class="space-y-1">
      <label class="text-xs text-slate-300" for="title">Title</label>
      <input id="title" name="title" type="text" required value="<?= e((string) ($product['title'] ?? '')) ?>" class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60" />
    </div>

    <div class="space-y-1">
      <label class="text-xs text-slate-300" for="categoryId">Kategori</label>
      <select id="categoryId" name="categoryId" required class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60">
        <option value="">Pilih kategori</option>
        <?php foreach (($categories ?? []) as $category): ?>
        <?php $isSelected = (int) ($product['category_id'] ?? 0) === (int) ($category['id'] ?? 0); ?>
        <option value="<?= (int) ($category['id'] ?? 0) ?>" <?= $isSelected ? 'selected' : '' ?>><?= e((string) ($category['name'] ?? '')) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <input type="hidden" name="price" value="0" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div class="space-y-1">
        <label class="text-xs text-slate-300" for="badgeStatus">Badge Status</label>
        <select id="badgeStatus" name="badgeStatus" class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60">
          <option value="none" <?= $selectedBadge === 'none' ? 'selected' : '' ?>>Tanpa Badge</option>
          <option value="hot" <?= $selectedBadge === 'hot' ? 'selected' : '' ?>>Hot</option>
          <option value="ready" <?= $selectedBadge === 'ready' ? 'selected' : '' ?>>Ready</option>
          <option value="limited" <?= $selectedBadge === 'limited' ? 'selected' : '' ?>>Limited</option>
          <option value="new" <?= $selectedBadge === 'new' ? 'selected' : '' ?>>New</option>
        </select>
      </div>
      <div class="space-y-1">
        <label class="text-xs text-slate-300" for="publishAt">Jadwal Publish</label>
        <input id="publishAt" name="publishAt" type="datetime-local" value="<?= e($publishAtValue) ?>" class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60" />
        <p class="text-[11px] text-slate-400">Kosongkan jika ingin langsung tayang.</p>
      </div>
    </div>

    <div class="space-y-1">
      <label class="text-xs text-slate-300" for="whatsappNumber">Link/Username Telegram</label>
      <input id="whatsappNumber" name="whatsappNumber" type="text" placeholder="contoh: t.me/jefa14 atau @jefa14" value="<?= e((string) ($product['whatsapp_number'] ?? cfg('telegram_default'))) ?>" class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60" />
    </div>

    <div class="space-y-1">
      <label class="text-xs text-slate-300" for="description">Deskripsi</label>
      <textarea id="description" name="description" rows="5" class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60"><?= e((string) ($product['description'] ?? '')) ?></textarea>
    </div>

    <div class="space-y-2">
      <label class="text-xs text-slate-300" for="images">Upload Gambar (JPG/PNG/WEBP, max 10 file)</label>
      <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple <?= $isCreate ? 'required' : '' ?> class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500/60" />

      <?php if (!$isCreate && !empty($images)): ?>
      <p class="text-xs text-slate-300">Centang gambar yang ingin dihapus:</p>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
        <?php foreach ($images as $img): ?>
        <label class="space-y-1 text-xs">
          <div class="aspect-[9/16] rounded-lg overflow-hidden border border-white/10 bg-black/30">
            <img src="<?= e((string) ($img['image_path'] ?? '')) ?>" alt="image" class="h-full w-full object-contain bg-black" />
          </div>
          <span class="flex items-center gap-1">
            <input type="checkbox" name="removeImageIds[]" value="<?= (int) ($img['id'] ?? 0) ?>" /> Hapus
          </span>
        </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-200">
      <input type="checkbox" name="isActive" <?= (int) ($product['is_active'] ?? 1) === 1 ? 'checked' : '' ?> /> Produk aktif
    </label>

    <button type="submit" class="w-full rounded-xl bg-emerald-500 text-black py-3 text-sm font-semibold hover:bg-emerald-400 active:scale-[0.99] transition">
      <?= $isCreate ? 'Simpan Produk' : 'Update Produk' ?>
    </button>
  </form>
</section>

