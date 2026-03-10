function errorHandler(err, req, res, next) {
  const status = err.status || 500;
  const message = err.message || "Terjadi kesalahan pada server.";

  if (req.path.startsWith("/admin")) {
    return res.status(status).render("layouts/admin", {
      title: "Error",
      view: "admin/error",
      errorMessage: message,
      currentPath: req.path
    });
  }

  return res.status(status).render("layouts/public", {
    title: "Error",
    view: "public/error",
    errorMessage: message,
    currentPath: req.path
  });
}

module.exports = errorHandler;