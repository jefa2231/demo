const jwt = require("jsonwebtoken");
const env = require("../config/env");
const { Admin } = require("../models");

async function requireAdmin(req, res, next) {
  try {
    const token = req.cookies?.[env.jwt.cookieName];
    if (!token) {
      return res.redirect("/admin/login");
    }

    const decoded = jwt.verify(token, env.jwt.secret);
    const admin = await Admin.findByPk(decoded.id);
    if (!admin) {
      res.clearCookie(env.jwt.cookieName);
      return res.redirect("/admin/login");
    }

    req.admin = admin;
    res.locals.currentAdmin = admin;
    return next();
  } catch (error) {
    res.clearCookie(env.jwt.cookieName);
    return res.redirect("/admin/login");
  }
}

module.exports = requireAdmin;