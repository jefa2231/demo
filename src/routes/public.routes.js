const express = require("express");
const productController = require("../controllers/product.controller");

const router = express.Router();

router.get("/", productController.getHome);
router.get("/katalog", productController.getCatalog);
router.get("/go/tg/:slug", productController.redirectToTelegram);
router.get("/p/:slug", productController.getProductDetail);
router.get("/c/:slug", productController.getCategoryProducts);

module.exports = router;
