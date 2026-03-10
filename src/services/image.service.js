const fs = require("fs/promises");
const path = require("path");
const sharp = require("sharp");
const env = require("../config/env");

const UPLOAD_DIR_ABS = env.upload.absDir || path.resolve(env.paths.rootDir, env.upload.dir);
const PUBLIC_UPLOAD_PREFIX = "/uploads/products";
const OG_UPLOAD_DIR_ABS = path.resolve(env.paths.rootDir, "src/public/uploads/og");
const PUBLIC_OG_PREFIX = "/uploads/og";

async function ensureUploadDir() {
  await fs.mkdir(UPLOAD_DIR_ABS, { recursive: true });
  await fs.mkdir(OG_UPLOAD_DIR_ABS, { recursive: true });
}

function cleanBaseName(slug) {
  return String(slug || "product").replace(/[^a-z0-9-]/gi, "-").toLowerCase();
}

function escapeSvgText(input) {
  return String(input || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function splitTextLines(text, maxCharsPerLine = 30, maxLines = 2) {
  const words = String(text || "").trim().split(/\s+/).filter(Boolean);
  if (words.length === 0) {
    return ["Produk Digital"];
  }

  const lines = [];
  let current = "";
  words.forEach((word) => {
    const next = current ? `${current} ${word}` : word;
    if (next.length <= maxCharsPerLine) {
      current = next;
      return;
    }

    if (current) {
      lines.push(current);
    }
    current = word;
  });

  if (current) {
    lines.push(current);
  }

  return lines.slice(0, maxLines).map((line, index, arr) => {
    if (index === arr.length - 1 && lines.length > arr.length) {
      return `${line.slice(0, Math.max(0, maxCharsPerLine - 1))}…`;
    }
    return line;
  });
}

function resolvePublicPathAbsolute(publicPath) {
  if (!publicPath || typeof publicPath !== "string" || !publicPath.startsWith("/uploads/")) {
    return null;
  }

  return path.resolve(env.paths.rootDir, "src/public", publicPath.replace(/^\//, ""));
}

async function generateProductOgCard({ productSlug, productTitle, productImagePath, appName }) {
  await ensureUploadDir();

  const safeName = cleanBaseName(productSlug || "product");
  const filename = `${safeName}-og.webp`;
  const outputPath = path.join(OG_UPLOAD_DIR_ABS, filename);

  const titleLines = splitTextLines(productTitle, 28, 2);
  const line1 = escapeSvgText(titleLines[0] || "Produk Digital");
  const line2 = escapeSvgText(titleLines[1] || "");
  const brandText = escapeSvgText(appName || "JeStore");

  const textOverlaySvg = `
<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="shade" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="rgba(2,6,23,0.72)"/>
      <stop offset="60%" stop-color="rgba(2,6,23,0.18)"/>
      <stop offset="100%" stop-color="rgba(2,6,23,0.86)"/>
    </linearGradient>
    <linearGradient id="chip" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="#22d3ee"/>
      <stop offset="100%" stop-color="#10b981"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#shade)"/>
  <rect x="52" y="52" width="246" height="44" rx="22" fill="rgba(2,6,23,0.75)" stroke="rgba(255,255,255,0.18)"/>
  <circle cx="82" cy="74" r="8" fill="url(#chip)"/>
  <text x="102" y="81" fill="#a7f3d0" font-size="21" font-family="Segoe UI, Arial, sans-serif" font-weight="700">${brandText}</text>
  <text x="56" y="484" fill="white" font-size="62" font-family="Segoe UI, Arial, sans-serif" font-weight="800">${line1}</text>
  ${line2 ? `<text x="56" y="552" fill="white" font-size="56" font-family="Segoe UI, Arial, sans-serif" font-weight="700">${line2}</text>` : ""}
  <rect x="56" y="576" width="300" height="4" rx="2" fill="url(#chip)" opacity="0.95"/>
</svg>`;

  const composition = [];
  const imageAbsolutePath = resolvePublicPathAbsolute(productImagePath);
  if (imageAbsolutePath) {
    try {
      const resizedImage = await sharp(imageAbsolutePath)
        .rotate()
        .resize(1200, 630, { fit: "cover" })
        .toBuffer();
      composition.push({ input: resizedImage, blend: "over" });
    } catch (error) {
      // fallback to plain background if product image is not readable
    }
  }

  composition.push({ input: Buffer.from(textOverlaySvg), blend: "over" });

  await sharp({
    create: {
      width: 1200,
      height: 630,
      channels: 3,
      background: { r: 2, g: 6, b: 23 }
    }
  })
    .composite(composition)
    .webp({ quality: 88 })
    .toFile(outputPath);

  return `${PUBLIC_OG_PREFIX}/${filename}`;
}

async function processImages(files, productSlug, startIndex = 0) {
  await ensureUploadDir();

  const base = cleanBaseName(productSlug);
  const timestamp = Date.now();

  const processed = [];
  for (let i = 0; i < files.length; i += 1) {
    const file = files[i];
    const filename = `${base}-${timestamp}-${startIndex + i + 1}.webp`;
    const outputPath = path.join(UPLOAD_DIR_ABS, filename);

    await sharp(file.buffer)
      .rotate()
      .resize({ width: 1080, withoutEnlargement: true })
      .webp({ quality: 80 })
      .toFile(outputPath);

    processed.push({
      filename,
      publicPath: `${PUBLIC_UPLOAD_PREFIX}/${filename}`
    });
  }

  return processed;
}

async function deleteImageByPublicPath(publicPath) {
  if (!publicPath || !publicPath.startsWith(PUBLIC_UPLOAD_PREFIX)) {
    return;
  }

  const filename = publicPath.replace(`${PUBLIC_UPLOAD_PREFIX}/`, "");
  const absolute = path.join(UPLOAD_DIR_ABS, filename);
  await fs.rm(absolute, { force: true });
}

async function deleteOgByPublicPath(publicPath) {
  if (!publicPath || !publicPath.startsWith(PUBLIC_OG_PREFIX)) {
    return;
  }

  const filename = publicPath.replace(`${PUBLIC_OG_PREFIX}/`, "");
  const absolute = path.join(OG_UPLOAD_DIR_ABS, filename);
  await fs.rm(absolute, { force: true });
}

module.exports = {
  ensureUploadDir,
  processImages,
  deleteImageByPublicPath,
  generateProductOgCard,
  deleteOgByPublicPath,
  PUBLIC_UPLOAD_PREFIX,
  PUBLIC_OG_PREFIX
};
