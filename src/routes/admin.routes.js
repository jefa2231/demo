const express = require("express");
const requireAdmin = require("../middleware/requireAdmin");
const upload = require("../middleware/uploadImages");
const authController = require("../controllers/auth.controller");
const adminController = require("../controllers/admin.controller");
const productController = require("../controllers/product.controller");
const categoryController = require("../controllers/category.controller");

const router = express.Router();

router.get("/login", authController.getLogin);
router.post("/login", authController.postLogin);
router.post("/logout", authController.postLogout);

router.use(requireAdmin);

router.get("/", adminController.getDashboard);

router.get("/products", productController.getAdminProducts);
router.get("/products/new", productController.getNewProductForm);
router.post("/products", upload.array("images"), productController.createProduct);
router.get("/products/:id/edit", productController.getEditProductForm);
router.post("/products/:id", upload.array("images"), productController.updateProduct);
router.post("/products/:id/delete", productController.deleteProduct);
router.post("/products/:id/toggle", productController.toggleProduct);

router.get("/categories", categoryController.getCategories);
router.post("/categories", categoryController.createCategory);
router.post("/categories/:id", categoryController.updateCategory);
router.post("/categories/:id/delete", categoryController.deleteCategory);

module.exports = router;