const multer = require("multer");
const env = require("../config/env");

const allowedMimes = new Set(["image/jpeg", "image/png", "image/webp"]);

const upload = multer({
  storage: multer.memoryStorage(),
  limits: {
    fileSize: env.upload.maxSizeBytes,
    files: env.upload.maxFiles
  },
  fileFilter(req, file, cb) {
    if (allowedMimes.has(file.mimetype)) {
      cb(null, true);
      return;
    }
    cb(new Error("File harus JPG, PNG, atau WEBP."));
  }
});

module.exports = upload;