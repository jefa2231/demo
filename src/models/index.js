const sequelize = require("../config/database");
const Admin = require("./Admin");
const Category = require("./Category");
const Product = require("./Product");
const ProductImage = require("./ProductImage");

Category.hasMany(Product, { foreignKey: "categoryId", as: "products" });
Product.belongsTo(Category, { foreignKey: "categoryId", as: "category" });

Product.hasMany(ProductImage, {
  foreignKey: "productId",
  as: "images",
  onDelete: "CASCADE",
  hooks: true
});
ProductImage.belongsTo(Product, { foreignKey: "productId", as: "product" });

module.exports = {
  sequelize,
  Admin,
  Category,
  Product,
  ProductImage
};