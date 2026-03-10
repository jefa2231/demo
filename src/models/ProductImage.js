const { DataTypes } = require("sequelize");
const sequelize = require("../config/database");

const ProductImage = sequelize.define(
  "ProductImage",
  {
    id: { type: DataTypes.INTEGER.UNSIGNED, primaryKey: true, autoIncrement: true },
    productId: {
      type: DataTypes.INTEGER.UNSIGNED,
      allowNull: false,
      field: "product_id",
      references: { model: "products", key: "id" }
    },
    imagePath: { type: DataTypes.STRING(255), allowNull: false, field: "image_path" },
    sortOrder: { type: DataTypes.INTEGER.UNSIGNED, allowNull: false, defaultValue: 0, field: "sort_order" }
  },
  {
    tableName: "product_images",
    updatedAt: false
  }
);

module.exports = ProductImage;