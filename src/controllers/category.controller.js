const slugify = require("slugify");
const { Op } = require("sequelize");
const { Category } = require("../models");

function normalizeName(name) {
  return String(name || "").trim();
}

async function generateUniqueCategorySlug(name, excludeId = null) {
  const base = slugify(name, { lower: true, strict: true }) || "kategori";
  let slug = base;
  let i = 1;

  while (true) {
    const existing = await Category.findOne({
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

async function getCategories(req, res) {
  const categories = await Category.findAll({ order: [["createdAt", "DESC"]] });

  return res.render("layouts/admin", {
    title: "Kategori",
    view: "admin/categories",
    currentPath: "/admin/categories",
    categories,
    errorMessage: req.query.error || "",
    successMessage: req.query.success || "",
    currentAdmin: req.admin
  });
}

async function createCategory(req, res) {
  const name = normalizeName(req.body.name);

  if (!name) {
    return res.redirect("/admin/categories?error=Nama%20kategori%20wajib%20diisi");
  }

  const slug = await generateUniqueCategorySlug(name);
  await Category.create({ name, slug });

  return res.redirect("/admin/categories?success=Kategori%20berhasil%20ditambah");
}

async function updateCategory(req, res) {
  const category = await Category.findByPk(req.params.id);
  if (!category) {
    return res.redirect("/admin/categories?error=Kategori%20tidak%20ditemukan");
  }

  const name = normalizeName(req.body.name);
  if (!name) {
    return res.redirect("/admin/categories?error=Nama%20kategori%20wajib%20diisi");
  }

  category.name = name;
  category.slug = await generateUniqueCategorySlug(name, category.id);
  await category.save();

  return res.redirect("/admin/categories?success=Kategori%20berhasil%20diupdate");
}

async function deleteCategory(req, res) {
  const category = await Category.findByPk(req.params.id);
  if (!category) {
    return res.redirect("/admin/categories?error=Kategori%20tidak%20ditemukan");
  }

  await category.destroy();
  return res.redirect("/admin/categories?success=Kategori%20berhasil%20dihapus");
}

module.exports = {
  getCategories,
  createCategory,
  updateCategory,
  deleteCategory,
  generateUniqueCategorySlug
};