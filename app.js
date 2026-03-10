const path = require("path");
const express = require("express");
const helmet = require("helmet");
const compression = require("compression");
const cookieParser = require("cookie-parser");
const methodOverride = require("method-override");

const env = require("./src/config/env");
const { sequelize } = require("./src/models");
const { ensureUploadDir } = require("./src/services/image.service");
const publicRoutes = require("./src/routes/public.routes");
const adminRoutes = require("./src/routes/admin.routes");
const apiRoutes = require("./src/routes/api.routes");
const errorHandler = require("./src/middleware/errorHandler");

const app = express();

app.set("view engine", "ejs");
app.set("views", path.join(__dirname, "src/views"));
if (env.trustProxy) {
  app.set("trust proxy", 1);
}

app.use(
  helmet({
    contentSecurityPolicy: false
  })
);
app.use(compression());
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(cookieParser());
app.use(methodOverride("_method"));

app.use((req, res, next) => {
  res.locals.appName = env.appName;
  res.locals.formatPrice = (value) => {
    const numberValue = Number(value);
    if (!Number.isFinite(numberValue) || numberValue <= 0) {
      return "";
    }
    return `Rp ${numberValue.toLocaleString("id-ID")}`;
  };
  res.locals.currentPath = req.path;
  res.locals.currentAdmin = null;
  next();
});

app.use(
  express.static(path.join(__dirname, "src/public"), {
    maxAge: env.isProd ? "7d" : 0,
    setHeaders(res, filePath) {
      if (filePath.endsWith(".webp")) {
        res.setHeader("Cache-Control", env.isProd ? "public, max-age=604800, immutable" : "no-cache");
      }
    }
  })
);

app.use("/api", apiRoutes);
app.use("/admin", adminRoutes);
app.use("/", publicRoutes);

app.use((req, res) => {
  res.status(404).render("layouts/public", {
    title: "404",
    view: "public/error",
    errorMessage: "Halaman tidak ditemukan."
  });
});

app.use(errorHandler);

async function start() {
  try {
    await ensureUploadDir();
    await sequelize.authenticate();

    app.listen(env.port, () => {
      console.log(`${env.appName} running on http://localhost:${env.port}`);
    });
  } catch (error) {
    console.error("Failed to start app:", error?.message || String(error));
    if (error?.stack) {
      console.error(error.stack);
    }
    process.exit(1);
  }
}

start();
