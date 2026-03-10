<section class="max-w-md mx-auto rounded-2xl border border-white/10 bg-slate-900 p-4 space-y-3">
  <h1 class="text-base font-semibold">Admin Login</h1>
  <?php if (!empty($errorMessage)): ?>
  <p class="text-sm rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-red-300"><?= e((string) $errorMessage) ?></p>
  <?php endif; ?>

  <form action="<?= e(local_url('/admin/login')) ?>" method="post" class="space-y-3">
    <div class="space-y-1">
      <label class="text-xs text-slate-300" for="username">Username</label>
      <input id="username" name="username" type="text" required class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100" />
    </div>
    <div class="space-y-1">
      <label class="text-xs text-slate-300" for="password">Password</label>
      <input id="password" name="password" type="password" required class="w-full rounded-xl bg-slate-950 border border-white/10 px-3 py-3 text-sm text-slate-100" />
    </div>
    <button type="submit" class="w-full rounded-xl bg-emerald-500 text-black py-3 text-sm font-semibold hover:bg-emerald-400">Masuk</button>
  </form>
</section>

