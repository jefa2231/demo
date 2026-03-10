const { DataTypes } = require("sequelize");
const sequelize = require("../config/database");

const Product = sequelize.define(
  "Product",
  {
    id: { type: DataTypes.INTEGER.UNSIGNED, primaryKey: true, autoIncrement: true },
    categoryId: {
      type: DataTypes.INTEGER.UNSIGNED,
      allowNull: false,
      field: "category_id",
      references: { model: "categories", key: "id" }
    },
    title: { type: DataTypes.STRING(180), allowNull: false },
    slug: { type: DataTypes.STRING(220), unique: true, allowNull: false },
    description: { type: DataTypes.TEXT, allowNull: true },
    price: { type: DataTypes.BIGINT.UNSIGNED, allowNull: false, defaultValue: 0 },
    whatsappNumber: { type: DataTypes.STRING(30), allowNull: true, field: "whatsapp_number" },
    isActive: { type: DataTypes.BOOLEAN, allowNull: false, defaultValue: true, field: "is_active" },
    badgeStatus: { type: DataTypes.STRING(20), allowNull: false, defaultValue: "none", field: "badge_status" },
    publishAt: { type: DataTypes.DATE, allowNull: true, field: "publish_at" },
    viewCount: { type: DataTypes.INTEGER.UNSIGNED, allowNull: false, defaultValue: 0, field: "view_count" },
    tgClickCount: {
      type: DataTypes.INTEGER.UNSIGNED,
      allowNull: false,
      defaultValue: 0,
      field: "telegram_click_count"
    },
    ogImagePath: { type: DataTypes.STRING(255), allowNull: true, field: "og_image_path" }
  },
  {
    tableName: "products"
  }
);

module.exports = Product;
