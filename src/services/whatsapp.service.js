function normalizeTelegramTarget(input) {
  const raw = String(input || "").trim();
  if (!raw) {
    return "";
  }

  if (raw.startsWith("http://") || raw.startsWith("https://")) {
    return raw.replace(/^http:\/\//i, "https://");
  }

  if (raw.startsWith("t.me/")) {
    return `https://${raw}`;
  }

  if (raw.startsWith("@")) {
    return `https://t.me/${raw.slice(1)}`;
  }

  return `https://t.me/${raw}`;
}

function buildTelegramUrl(target, message) {
  const baseUrl = normalizeTelegramTarget(target);
  const text = encodeURIComponent(message || "Halo, saya tertarik dengan produk ini.");
  if (!baseUrl) {
    return "#";
  }
  return `${baseUrl}?text=${text}`;
}

function buildProductMessage(product) {
  return `Halo, saya mau order:\n- Produk: ${product.title}\n- Link: ${product.slug ? `/p/${product.slug}` : ""}`;
}

module.exports = {
  buildTelegramUrl,
  buildProductMessage,
  normalizeTelegramTarget
};
