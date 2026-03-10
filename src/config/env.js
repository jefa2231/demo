const path = require("path");
const dotenv = require("dotenv");

const ROOT_DIR = path.resolve(__dirname, "../..");

dotenv.config({ path: process.env.DOTENV_CONFIG_PATH || path.resolve(ROOT_DIR, ".env") });

const toNumber = (value, fallback) => {
  const n = Number(value);
  return Number.isFinite(n) && n > 0 ? n : fallback;
};

const toBoolean = (value, fallback) => {
  if (value === undefined || value === null || value === "") {
    return fallback;
  }

  const normalized = String(value).trim().toLowerCase();
  if (["1", "true", "yes", "on"].includes(normalized)) {
    return true;
  }
  if (["0", "false", "no", "off"].includes(normalized)) {
    return false;
  }

  return fallback;
};

const nodeEnv = process.env.NODE_ENV || "development";
const isProd = nodeEnv === "production";
const uploadDir = process.env.UPLOAD_DIR || "src/public/uploads/products";
const cookieSecureRaw = String(process.env.COOKIE_SECURE || "auto").trim().toLowerCase();
const cookieSecure = cookieSecureRaw === "auto" ? isProd : toBoolean(cookieSecureRaw, isProd);

module.exports = {
  nodeEnv,
  isProd,
  paths: {
    rootDir: ROOT_DIR
  },
  port: toNumber(process.env.PORT, 3000),
  appName: process.env.APP_NAME || "JeStore",
  trustProxy: toBoolean(process.env.TRUST_PROXY, isProd),
  db: {
    host: process.env.DB_HOST || "localhost",
    port: toNumber(process.env.DB_PORT, 3306),
    user: process.env.DB_USER || "root",
    pass: process.env.DB_PASS || "",
    name: process.env.DB_NAME || "lazastore"
  },
  jwt: {
    secret: process.env.JWT_SECRET || "dev-secret",
    expiresIn: process.env.JWT_EXPIRES_IN || "7d",
    cookieName: process.env.ADMIN_COOKIE_NAME || "admin_token"
  },
  cookie: {
    secure: cookieSecure,
    sameSite: process.env.COOKIE_SAME_SITE || "lax"
  },
  upload: {
    dir: uploadDir,
    absDir: path.resolve(ROOT_DIR, uploadDir),
    maxFiles: toNumber(process.env.MAX_UPLOAD_FILES, 10),
    maxSizeBytes: toNumber(process.env.MAX_UPLOAD_SIZE_MB, 2) * 1024 * 1024
  },
  telegramDefault: process.env.TELEGRAM_DEFAULT || process.env.WHATSAPP_DEFAULT || "t.me/jefa14"
};
