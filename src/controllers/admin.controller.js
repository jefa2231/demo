const { Op, fn, col } = require("sequelize");
const { Category, Product } = require("../models");

async function getDashboard(req, res) {
  const now = new Date();
  const [productsCount, categoriesCount, activeProductsCount, scheduledProductsCount, totals, topProducts] = await Promise.all([
    Product.count(),
    Category.count(),
    Product.count({ where: { isActive: true } }),
    Product.count({ where: { isActive: true, publishAt: { [Op.gt]: now } } }),
    Product.findOne({
      attributes: [
        [fn("COALESCE", fn("SUM", col("view_count")), 0), "totalViews"],
        [fn("COALESCE", fn("SUM", col("telegram_click_count")), 0), "totalClicks"]
      ],
      raw: true
    }),
    Product.findAll({
      attributes: ["id", "title", "slug", "viewCount", "tgClickCount", "badgeStatus", "publishAt"],
      where: { isActive: true },
      order: [["tgClickCount", "DESC"], ["viewCount", "DESC"], ["createdAt", "DESC"]],
      limit: 8
    })
  ]);

  const totalViews = Number(totals?.totalViews || 0);
  const totalClicks = Number(totals?.totalClicks || 0);
  const totalCtr = totalViews > 0 ? (totalClicks / totalViews) * 100 : 0;

  const rankedProducts = topProducts.map((product, index) => {
    const views = Number(product.viewCount || 0);
    const clicks = Number(product.tgClickCount || 0);
    const ctr = views > 0 ? (clicks / views) * 100 : 0;
    return {
      rank: index + 1,
      id: product.id,
      slug: product.slug,
      title: product.title,
      views,
      clicks,
      ctr,
      badgeStatus: product.badgeStatus || "none",
      publishAt: product.publishAt
    };
  });

  return res.render("layouts/admin", {
    title: "Dashboard",
    view: "admin/dashboard",
    currentPath: "/admin",
    stats: {
      productsCount,
      categoriesCount,
      activeProductsCount,
      scheduledProductsCount,
      totalViews,
      totalClicks,
      totalCtr
    },
    topProducts: rankedProducts,
    currentAdmin: req.admin
  });
}

module.exports = {
  getDashboard
};
