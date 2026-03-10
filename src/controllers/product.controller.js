const slugify = require("slugify");
const { Op, fn, col } = require("sequelize");
const env = require("../config/env");
const { sequelize, Category, Product, ProductImage } = require("../models");
const {
  processImages,
  deleteImageByPublicPath,
  generateProductOgCard,
  deleteOgByPublicPath
} = require("../services/image.service");
const { buildProductMessage, buildTelegramUrl } = require("../services/whatsapp.service");

function parsePrice(value) {
  const cleaned = String(value || "").replace(/[^0-9]/g, "");
  return Number(cleaned || "0");
}

function parseNullablePrice(value) {
  const cleaned = String(value || "").replace(/[^0-9]/g, "");
  if (!cleaned) {
    return null;
  }

  const parsed = Number(cleaned);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
}

function parseNullableDateTime(value) {
  const normalized = normalizeString(value);
  if (!normalized) {
    return null;
  }

  const parsedDate = new Date(normalized);
  return Number.isFinite(parsedDate.getTime()) ? parsedDate : null;
}

function parsePositiveInt(value, fallback = 1) {
  const parsed = Number.parseInt(value, 10);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

function parseBoolean(value) {
  return ["on", "true", "1", true, 1].includes(value);
}

function normalizeString(value) {
  return String(value || "").trim();
}

function resolveTelegramTarget(value) {
  const normalized = normalizeString(value);
  if (!normalized) {
    return env.telegramDefault;
  }

  if (/^[0-9+\s-]+$/.test(normalized)) {
    return env.telegramDefault;
  }

  return normalized;
}

function toArray(value) {
  if (!value) {
    return [];
  }
  return Array.isArray(value) ? value : [value];
}

function normalizeBadgeStatus(value) {
  const normalized = normalizeString(value).toLowerCase();
  const allowed = new Set(["none", "hot", "ready", "limited", "new"]);
  return allowed.has(normalized) ? normalized : "none";
}

function getBadgeMeta(status) {
  const mapping = {
    hot: { key: "hot", label: "Hot", className: "badge-hot" },
    ready: { key: "ready", label: "Ready", className: "badge-ready" },
    limited: { key: "limited", label: "Limited", className: "badge-limited" },
    new: { key: "new", label: "New", className: "badge-new" }
  };

  return mapping[status] || null;
}

function normalizeCatalogSort(value) {
  const normalized = normalizeString(value).toLowerCase();
  if (normalized === "oldest") {
    return "oldest";
  }
  return "newest";
}

function getProductOrder(sort = "newest") {
  if (sort === "oldest") {
    return [["createdAt", "ASC"]];
  }
  return [["createdAt", "DESC"]];
}

function getPublicVisibilityWhere(now = new Date()) {
  return {
    isActive: true,
    [Op.or]: [{ publishAt: null }, { publishAt: { [Op.lte]: now } }]
  };
}

function isProductPubliclyVisible(product, now = new Date()) {
  if (!product || !product.isActive) {
    return false;
  }
  if (!product.publishAt) {
    return true;
  }
  return new Date(product.publishAt).getTime() <= now.getTime();
}

function sortProductImages(products) {
  products.forEach((product) => {
    if (product?.images?.length) {
      product.images.sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0));
    }
  });
}

function attachPublicMeta(products) {
  const now = Date.now();
  const newProductMs = 7 * 24 * 60 * 60 * 1000;

  products.forEach((product) => {
    const telegramTarget = resolveTelegramTarget(product.whatsappNumber);
    const tgUrl = buildTelegramUrl(telegramTarget, buildProductMessage(product));
    product.setDataValue("tgUrl", tgUrl);
    product.setDataValue("tgTrackUrl", `/go/tg/${product.slug}`);

    const createdAtMs = product.createdAt ? new Date(product.createdAt).getTime() : 0;
    const isNew = createdAtMs > 0 && now - createdAtMs <= newProductMs;
    product.setDataValue("isNew", isNew);

    const explicitBadgeStatus = normalizeBadgeStatus(product.badgeStatus);
    const effectiveBadgeStatus = explicitBadgeStatus === "none" && isNew ? "new" : explicitBadgeStatus;
    const badgeMeta = getBadgeMeta(effectiveBadgeStatus);
    if (badgeMeta) {
      product.setDataValue("badgeKey", badgeMeta.key);
      product.setDataValue("badgeLabel", badgeMeta.label);
      product.setDataValue("badgeClass", badgeMeta.className);
    } else {
      product.setDataValue("badgeKey", "");
      product.setDataValue("badgeLabel", "");
      product.setDataValue("badgeClass", "");
    }
  });
}

function buildCatalogUrl({ q = "", category = "", minPrice = null, maxPrice = null, sort = "newest", page = 1 }) {
  const params = new URLSearchParams();
  if (q) {
    params.set("q", q);
  }
  if (category) {
    params.set("category", category);
  }
  if (minPrice !== null && minPrice !== undefined && Number.isFinite(Number(minPrice)) && Number(minPrice) >= 0) {
    params.set("minPrice", String(Number(minPrice)));
  }
  if (maxPrice !== null && maxPrice !== undefined && Number.isFinite(Number(maxPrice)) && Number(maxPrice) >= 0) {
    params.set("maxPrice", String(Number(maxPrice)));
  }
  if (sort && sort !== "newest") {
    params.set("sort", sort);
  }
  if (Number(page) > 1) {
    params.set("page", String(Number(page)));
  }

  const query = params.toString();
  return query ? `/katalog?${query}` : "/katalog";
}

function extractKeywords(value) {
  const stopwords = new Set([
    "dan",
    "atau",
    "untuk",
    "yang",
    "dengan",
    "the",
    "and",
    "for",
    "from",
    "via",
    "paket",
    "produk",
    "akun"
  ]);

  const words = String(value || "")
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, " ")
    .split(/\s+/)
    .map((word) => word.trim())
    .filter((word) => word.length >= 3 && !stopwords.has(word));

  return Array.from(new Set(words)).slice(0, 8);
}

function computeRelatedScore(sourceProduct, candidateProduct, sourceKeywords) {
  const candidateKeywords = new Set(extractKeywords(candidateProduct.title));
  const overlapCount = sourceKeywords.filter((word) => candidateKeywords.has(word)).length;
  const sameCategory = Number(sourceProduct.categoryId) === Number(candidateProduct.categoryId);

  const createdAtMs = candidateProduct.createdAt ? new Date(candidateProduct.createdAt).getTime() : 0;
  const recencyScore = createdAtMs > 0 ? Math.max(0, 10 - Math.floor((Date.now() - createdAtMs) / (24 * 60 * 60 * 1000))) : 0;

  return (sameCategory ? 40 : 0) + overlapCount * 12 + recencyScore;
}

function buildAbsoluteUrl(req, pathValue = "") {
  const baseUrl = `${req.protocol}://${req.get("host")}`;
  if (!pathValue) {
    return baseUrl;
  }
  return pathValue.startsWith("http") ? pathValue : `${baseUrl}${pathValue}`;
}

async function ensureProductOgCard(product, req = null) {
  if (!product) {
    return null;
  }

  const firstImagePath = product.images?.[0]?.imagePath || null;
  if (!product.ogImagePath) {
    const generatedPath = await generateProductOgCard({
      productSlug: product.slug,
      productTitle: product.title,
      productImagePath: firstImagePath,
      appName: env.appName
    });

    if (generatedPath) {
      await Product.update({ ogImagePath: generatedPath }, { where: { id: product.id } });
      product.setDataValue("ogImagePath", generatedPath);
    }
  }

  const imageForShare = product.ogImagePath || firstImagePath || "";
  return req ? buildAbsoluteUrl(req, imageForShare) : imageForShare;
}

async function refreshProductOgCardById(productId, previousOgPath = "") {
  const latestProduct = await Product.findByPk(productId, {
    include: [{ model: ProductImage, as: "images" }]
  });
  if (!latestProduct) {
    return;
  }

  sortProductImages([latestProduct]);
  const firstImagePath = latestProduct.images?.[0]?.imagePath || null;
  const generatedPath = await generateProductOgCard({
    productSlug: latestProduct.slug,
    productTitle: latestProduct.title,
    productImagePath: firstImagePath,
    appName: env.appName
  });

  if (generatedPath && generatedPath !== latestProduct.ogImagePath) {
    latestProduct.ogImagePath = generatedPath;
    await latestProduct.save();
  }

  if (previousOgPath && previousOgPath !== generatedPath) {
    await deleteOgByPublicPath(previousOgPath);
  }
}

async function getSmartRelatedProducts(product, limit = 6) {
  const titleKeywords = extractKeywords(product.title);
  const keywordClauses = titleKeywords.map((word) => ({
    title: { [Op.like]: `%${word}%` }
  }));

  const where = {
    ...getPublicVisibilityWhere(),
    id: { [Op.ne]: product.id }
  };

  if (keywordClauses.length > 0) {
    where[Op.or] = [{ categoryId: product.categoryId }, ...keywordClauses];
  } else {
    where.categoryId = product.categoryId;
  }

  const candidates = await Product.findAll({
    where,
    include: [
      { model: Category, as: "category" },
      { model: ProductImage, as: "images" }
    ],
    order: [["createdAt", "DESC"]],
    limit: 40
  });

  sortProductImages(candidates);
  attachPublicMeta(candidates);

  const scored = candidates
    .map((candidate) => ({
      candidate,
      score: computeRelatedScore(product, candidate, titleKeywords)
    }))
    .sort((a, b) => b.score - a.score)
    .slice(0, limit)
    .map((entry) => entry.candidate);

  return scored;
}

async function getCategoriesWithCounts(now = new Date()) {
  const [categories, totals] = await Promise.all([
    Category.findAll({ order: [["name", "ASC"]] }),
    Product.findAll({
      attributes: ["categoryId", [fn("COUNT", col("id")), "total"]],
      where: getPublicVisibilityWhere(now),
      group: ["category_id"],
      raw: true
    })
  ]);

  const totalsMap = new Map(
    totals.map((row) => [Number(row.categoryId), Number(row.total || 0)])
  );

  return categories.map((category) => {
    const plain = category.get({ plain: true });
    return {
      ...plain,
      total: totalsMap.get(category.id) || 0
    };
  });
}

async function generateUniqueProductSlug(title, excludeId = null) {
  const base = slugify(title, { lower: true, strict: true }) || "produk";
  let slug = base;
  let i = 1;

  while (true) {
    const existing = await Product.findOne({
      where: {
        slug,
        ...(excludeId ? { id: { [Op.ne]: excludeId } } : {})
      }
    });

    if (!existing) {
      return slug;
    }

    slug = `${base}-${i}`;
    i += 1;
  }
}

async function renderAdminProductForm(res, options) {
  const categories = await Category.findAll({ order: [["name", "ASC"]] });

  return res.render("layouts/admin", {
    title: options.pageTitle,
    view: "admin/product-form",
    currentPath: options.currentPath,
    mode: options.mode,
    product: options.product,
    categories,
    errorMessage: options.errorMessage || "",
    successMessage: options.successMessage || "",
    currentAdmin: options.currentAdmin
  });
}

async function getHome(req, res) {
  const now = new Date();
  const [categories, featuredProducts] = await Promise.all([
    getCategoriesWithCounts(now),
    Product.findAll({
      where: getPublicVisibilityWhere(now),
      include: [
        { model: Category, as: "category" },
        { model: ProductImage, as: "images" }
      ],
      order: [["createdAt", "DESC"]]
    })
  ]);
  sortProductImages(featuredProducts);
  attachPublicMeta(featuredProducts);

  return res.render("layouts/public", {
    title: env.appName,
    view: "public/home",
    currentPath: "/",
    categories,
    featuredProducts,
    metaDescription: `Katalog digital ${env.appName} dengan preview produk cepat dan rapi.`,
    metaUrl: buildAbsoluteUrl(req, "/")
  });
}

async function getCatalog(req, res) {
  const now = new Date();
  const q = normalizeString(req.query.q);
  const categorySlug = normalizeString(req.query.category);
  const sort = normalizeCatalogSort(req.query.sort);
  let minPrice = parseNullablePrice(req.query.minPrice);
  let maxPrice = parseNullablePrice(req.query.maxPrice);
  const requestedPage = parsePositiveInt(req.query.page, 1);
  const perPage = 12;

  if (minPrice !== null && maxPrice !== null && minPrice > maxPrice) {
    const temp = minPrice;
    minPrice = maxPrice;
    maxPrice = temp;
  }

  let selectedCategory = null;
  if (categorySlug) {
    selectedCategory = await Category.findOne({ where: { slug: categorySlug } });
  }

  const where = getPublicVisibilityWhere(now);
  if (q) {
    where.title = { [Op.like]: `%${q}%` };
  }
  if (selectedCategory) {
    where.categoryId = selectedCategory.id;
  }
  if (minPrice !== null || maxPrice !== null) {
    where.price = {};
    if (minPrice !== null) {
      where.price[Op.gte] = minPrice;
    }
    if (maxPrice !== null) {
      where.price[Op.lte] = maxPrice;
    }
  }

  const [categories, productsResult] = await Promise.all([
    getCategoriesWithCounts(now),
    Product.findAndCountAll({
      where,
      include: [
        { model: Category, as: "category" },
        { model: ProductImage, as: "images" }
      ],
      order: getProductOrder(sort),
      limit: perPage,
      offset: (requestedPage - 1) * perPage,
      distinct: true
    })
  ]);

  const totalProducts = Number(productsResult.count || 0);
  const totalPages = totalProducts > 0 ? Math.ceil(totalProducts / perPage) : 1;
  if (requestedPage > totalPages && totalProducts > 0) {
    return res.redirect(
      buildCatalogUrl({
        q,
        category: selectedCategory ? selectedCategory.slug : "",
        minPrice,
        maxPrice,
        sort,
        page: totalPages
      })
    );
  }
  const currentPage = Math.min(requestedPage, totalPages);
  const products = productsResult.rows;
  sortProductImages(products);
  attachPublicMeta(products);

  const activeCategorySlug = selectedCategory ? selectedCategory.slug : "";
  const totalActiveProducts = categories.reduce((sum, item) => sum + Number(item.total || 0), 0);

  const categoryLinks = [
    {
      name: "Semua",
      slug: "",
      total: totalActiveProducts,
      active: !activeCategorySlug,
      url: buildCatalogUrl({ q, minPrice, maxPrice, sort })
    },
    ...categories.map((category) => ({
      name: category.name,
      slug: category.slug,
      total: category.total,
      active: activeCategorySlug === category.slug,
      url: buildCatalogUrl({ q, category: category.slug, minPrice, maxPrice, sort })
    }))
  ];

  const sortLinks = [
    {
      key: "newest",
      label: "Terbaru",
      active: sort === "newest",
      url: buildCatalogUrl({
        q,
        category: activeCategorySlug,
        minPrice,
        maxPrice,
        sort: "newest"
      })
    },
    {
      key: "oldest",
      label: "Terlama",
      active: sort === "oldest",
      url: buildCatalogUrl({
        q,
        category: activeCategorySlug,
        minPrice,
        maxPrice,
        sort: "oldest"
      })
    }
  ];

  const pagination = {
    currentPage,
    totalPages,
    totalProducts,
    hasPrev: currentPage > 1,
    hasNext: currentPage < totalPages,
    prevUrl: buildCatalogUrl({
      q,
      category: activeCategorySlug,
      minPrice,
      maxPrice,
      sort,
      page: currentPage - 1
    }),
    nextUrl: buildCatalogUrl({
      q,
      category: activeCategorySlug,
      minPrice,
      maxPrice,
      sort,
      page: currentPage + 1
    }),
    pages: []
  };

  const pageStart = Math.max(1, currentPage - 2);
  const pageEnd = Math.min(totalPages, currentPage + 2);
  for (let page = pageStart; page <= pageEnd; page += 1) {
    pagination.pages.push({
      page,
      active: page === currentPage,
      url: buildCatalogUrl({
        q,
        category: activeCategorySlug,
        minPrice,
        maxPrice,
        sort,
        page
      })
    });
  }

  return res.render("layouts/public", {
    title: "Katalog Produk",
    view: "public/catalog",
    currentPath: "/katalog",
    products,
    categories,
    q,
    minPrice,
    maxPrice,
    sort,
    selectedCategory,
    categoryLinks,
    sortLinks,
    pagination,
    metaDescription: "Cari katalog produk digital, filter kategori, dan pilih item yang kamu butuhkan.",
    metaUrl: buildAbsoluteUrl(req, req.originalUrl)
  });
}

async function getProductDetail(req, res, next) {
  const now = new Date();
  const product = await Product.findOne({
    where: {
      slug: req.params.slug,
      ...getPublicVisibilityWhere(now)
    },
    include: [
      { model: Category, as: "category" },
      { model: ProductImage, as: "images" }
    ]
  });

  if (!product) {
    return next({ status: 404, message: "Produk tidak ditemukan." });
  }

  await Product.increment("viewCount", { by: 1, where: { id: product.id } });
  product.setDataValue("viewCount", Number(product.viewCount || 0) + 1);

  sortProductImages([product]);
  attachPublicMeta([product]);
  const tgUrl = product.getDataValue("tgTrackUrl");
  const shareUrl = buildAbsoluteUrl(req, `/p/${product.slug}`);

  let ogImageUrl = "";
  try {
    ogImageUrl = await ensureProductOgCard(product, req);
  } catch (error) {
    ogImageUrl = product.images?.[0]?.imagePath ? buildAbsoluteUrl(req, product.images[0].imagePath) : "";
  }

  const relatedProducts = await getSmartRelatedProducts(product, 4);

  const plainDescription = normalizeString(product.description || "");
  const metaDescription = plainDescription
    ? plainDescription.slice(0, 160)
    : `Preview ${product.title} di ${env.appName}, langsung lanjut order via Telegram.`;

  return res.render("layouts/public", {
    title: product.title,
    view: "public/product-detail",
    currentPath: `/p/${product.slug}`,
    product,
    tgUrl,
    shareUrl,
    relatedProducts,
    ogImageUrl,
    metaDescription,
    metaUrl: shareUrl
  });
}

async function redirectToTelegram(req, res) {
  const product = await Product.findOne({
    where: { slug: req.params.slug },
    include: [{ model: ProductImage, as: "images" }]
  });

  if (!product || !isProductPubliclyVisible(product)) {
    return res.redirect("/katalog");
  }

  const telegramTarget = resolveTelegramTarget(product.whatsappNumber);
  const tgUrl = buildTelegramUrl(telegramTarget, buildProductMessage(product));
  await Product.increment("tgClickCount", { by: 1, where: { id: product.id } });

  if (!tgUrl || tgUrl === "#") {
    return res.redirect(`/p/${product.slug}`);
  }

  return res.redirect(tgUrl);
}

async function getCategoryProducts(req, res, next) {
  const category = await Category.findOne({ where: { slug: req.params.slug } });
  if (!category) {
    return next({ status: 404, message: "Kategori tidak ditemukan." });
  }

  const query = new URLSearchParams({ category: category.slug }).toString();
  return res.redirect(`/katalog?${query}`);
}

async function getAdminProducts(req, res) {
  const products = await Product.findAll({
    include: [
      { model: Category, as: "category" },
      { model: ProductImage, as: "images" }
    ],
    order: [["createdAt", "DESC"], [{ model: ProductImage, as: "images" }, "sortOrder", "ASC"]]
  });

  return res.render("layouts/admin", {
    title: "Produk",
    view: "admin/products",
    currentPath: "/admin/products",
    products,
    errorMessage: req.query.error || "",
    successMessage: req.query.success || "",
    currentAdmin: req.admin
  });
}

async function getNewProductForm(req, res) {
  return renderAdminProductForm(res, {
    pageTitle: "Tambah Produk",
    currentPath: "/admin/products/new",
    mode: "create",
    product: {
      id: null,
      title: "",
      price: 0,
      description: "",
      categoryId: "",
      badgeStatus: "none",
      publishAt: null,
      whatsappNumber: env.telegramDefault,
      isActive: true,
      images: []
    },
    currentAdmin: req.admin
  });
}

async function createProduct(req, res) {
  const title = normalizeString(req.body.title);
  const description = normalizeString(req.body.description);
  const categoryId = Number(req.body.categoryId);
  const price = 0;
  const badgeStatus = normalizeBadgeStatus(req.body.badgeStatus);
  const publishAt = parseNullableDateTime(req.body.publishAt);
  const whatsappNumber = normalizeString(req.body.whatsappNumber);
  const isActive = parseBoolean(req.body.isActive);
  const files = req.files || [];

  if (!title || !categoryId) {
    return renderAdminProductForm(res, {
      pageTitle: "Tambah Produk",
      currentPath: "/admin/products/new",
      mode: "create",
      product: {
        id: null,
        title,
        price,
        description,
        categoryId,
        badgeStatus,
        publishAt,
        whatsappNumber,
        isActive,
        images: []
      },
      errorMessage: "Title dan kategori wajib diisi.",
      currentAdmin: req.admin
    });
  }

  if (files.length < 1) {
    return renderAdminProductForm(res, {
      pageTitle: "Tambah Produk",
      currentPath: "/admin/products/new",
      mode: "create",
      product: {
        id: null,
        title,
        price,
        description,
        categoryId,
        badgeStatus,
        publishAt,
        whatsappNumber,
        isActive,
        images: []
      },
      errorMessage: "Minimal 1 gambar harus di-upload.",
      currentAdmin: req.admin
    });
  }

  const slug = await generateUniqueProductSlug(title);
  const tx = await sequelize.transaction();
  let processedImages = [];

  try {
    const product = await Product.create(
      {
        title,
        slug,
        description,
        categoryId,
        price,
        badgeStatus,
        publishAt,
        whatsappNumber,
        isActive
      },
      { transaction: tx }
    );

    processedImages = await processImages(files, slug, 0);

    await ProductImage.bulkCreate(
      processedImages.map((item, index) => ({
        productId: product.id,
        imagePath: item.publicPath,
        sortOrder: index + 1
      })),
      { transaction: tx }
    );

    await tx.commit();
    try {
      await refreshProductOgCardById(product.id);
    } catch (error) {
      // og card generation is non-blocking for product publishing flow
    }

    return res.redirect("/admin/products?success=Produk%20berhasil%20ditambahkan");
  } catch (error) {
    await tx.rollback();
    await Promise.allSettled(processedImages.map((item) => deleteImageByPublicPath(item.publicPath)));
    throw error;
  }
}

async function getEditProductForm(req, res) {
  const product = await Product.findByPk(req.params.id, {
    include: [{ model: ProductImage, as: "images" }],
    order: [[{ model: ProductImage, as: "images" }, "sortOrder", "ASC"]]
  });

  if (!product) {
    return res.redirect("/admin/products?error=Produk%20tidak%20ditemukan");
  }

  return renderAdminProductForm(res, {
    pageTitle: `Edit ${product.title}`,
    currentPath: "/admin/products",
    mode: "edit",
    product,
    currentAdmin: req.admin
  });
}

async function updateProduct(req, res) {
  const product = await Product.findByPk(req.params.id, {
    include: [{ model: ProductImage, as: "images" }],
    order: [[{ model: ProductImage, as: "images" }, "sortOrder", "ASC"]]
  });

  if (!product) {
    return res.redirect("/admin/products?error=Produk%20tidak%20ditemukan");
  }

  const title = normalizeString(req.body.title);
  const description = normalizeString(req.body.description);
  const categoryId = Number(req.body.categoryId);
  const price = 0;
  const badgeStatus = normalizeBadgeStatus(req.body.badgeStatus);
  const publishAt = parseNullableDateTime(req.body.publishAt);
  const whatsappNumber = normalizeString(req.body.whatsappNumber);
  const isActive = parseBoolean(req.body.isActive);
  const previousOgPath = product.ogImagePath || "";

  const removeImageIds = new Set(
    toArray(req.body.removeImageIds)
      .map((id) => Number(id))
      .filter((id) => Number.isInteger(id) && id > 0)
  );

  const currentImages = product.images || [];
  const imagesToKeep = currentImages.filter((img) => !removeImageIds.has(img.id));
  const imagesToRemove = currentImages.filter((img) => removeImageIds.has(img.id));
  const files = req.files || [];

  if (!title || !categoryId) {
    product.set({
      title,
      description,
      categoryId,
      price,
      badgeStatus,
      publishAt,
      whatsappNumber,
      isActive
    });
    return renderAdminProductForm(res, {
      pageTitle: `Edit ${product.title}`,
      currentPath: "/admin/products",
      mode: "edit",
      product,
      errorMessage: "Title dan kategori wajib diisi.",
      currentAdmin: req.admin
    });
  }

  if (imagesToKeep.length + files.length < 1) {
    product.set({
      title,
      description,
      categoryId,
      price,
      badgeStatus,
      publishAt,
      whatsappNumber,
      isActive
    });
    return renderAdminProductForm(res, {
      pageTitle: `Edit ${product.title}`,
      currentPath: "/admin/products",
      mode: "edit",
      product,
      errorMessage: "Produk harus punya minimal 1 gambar.",
      currentAdmin: req.admin
    });
  }

  const slug = await generateUniqueProductSlug(title, product.id);
  const tx = await sequelize.transaction();
  let newlyProcessed = [];

  try {
    product.title = title;
    product.slug = slug;
    product.description = description;
    product.categoryId = categoryId;
    product.price = price;
    product.badgeStatus = badgeStatus;
    product.publishAt = publishAt;
    product.whatsappNumber = whatsappNumber;
    product.isActive = isActive;

    await product.save({ transaction: tx });

    if (imagesToRemove.length > 0) {
      await ProductImage.destroy({
        where: {
          id: { [Op.in]: imagesToRemove.map((img) => img.id) },
          productId: product.id
        },
        transaction: tx
      });
    }

    if (files.length > 0) {
      const maxSort = imagesToKeep.reduce((max, img) => Math.max(max, img.sortOrder || 0), 0);
      newlyProcessed = await processImages(files, slug, maxSort);

      await ProductImage.bulkCreate(
        newlyProcessed.map((item, index) => ({
          productId: product.id,
          imagePath: item.publicPath,
          sortOrder: maxSort + index + 1
        })),
        { transaction: tx }
      );
    }

    await tx.commit();

    if (imagesToRemove.length > 0) {
      await Promise.allSettled(imagesToRemove.map((img) => deleteImageByPublicPath(img.imagePath)));
    }

    try {
      await refreshProductOgCardById(product.id, previousOgPath);
    } catch (error) {
      // og card regeneration is non-blocking for product update flow
    }

    return res.redirect("/admin/products?success=Produk%20berhasil%20diupdate");
  } catch (error) {
    await tx.rollback();
    await Promise.allSettled(newlyProcessed.map((item) => deleteImageByPublicPath(item.publicPath)));
    throw error;
  }
}

async function deleteProduct(req, res) {
  const product = await Product.findByPk(req.params.id, {
    include: [{ model: ProductImage, as: "images" }]
  });

  if (!product) {
    return res.redirect("/admin/products?error=Produk%20tidak%20ditemukan");
  }

  const imagePaths = (product.images || []).map((img) => img.imagePath);
  const ogImagePath = product.ogImagePath;
  await product.destroy();
  await Promise.allSettled(imagePaths.map((path) => deleteImageByPublicPath(path)));
  if (ogImagePath) {
    await deleteOgByPublicPath(ogImagePath);
  }

  return res.redirect("/admin/products?success=Produk%20berhasil%20dihapus");
}

async function toggleProduct(req, res) {
  const product = await Product.findByPk(req.params.id);
  if (!product) {
    return res.redirect("/admin/products?error=Produk%20tidak%20ditemukan");
  }

  product.isActive = !product.isActive;
  await product.save();

  return res.redirect("/admin/products?success=Status%20produk%20diupdate");
}

module.exports = {
  getHome,
  getCatalog,
  getProductDetail,
  redirectToTelegram,
  getCategoryProducts,
  getAdminProducts,
  getNewProductForm,
  createProduct,
  getEditProductForm,
  updateProduct,
  deleteProduct,
  toggleProduct
};
