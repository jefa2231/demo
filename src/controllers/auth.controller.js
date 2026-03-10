const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const env = require("../config/env");
const { Admin } = require("../models");

function renderLogin(res, options = {}) {
  return res.render("layouts/admin", {
    title: "Admin Login",
    view: "admin/login",
    currentPath: "/admin/login",
    errorMessage: options.errorMessage || ""
  });
}

async function getLogin(req, res) {
  const token = req.cookies?.[env.jwt.cookieName];
  if (!token) {
    return renderLogin(res);
  }

  try {
    jwt.verify(token, env.jwt.secret);
    return res.redirect("/admin");
  } catch (error) {
    res.clearCookie(env.jwt.cookieName);
    return renderLogin(res);
  }
}

async function postLogin(req, res) {
  const { username, password } = req.body;

  if (!username || !password) {
    return renderLogin(res, { errorMessage: "Username dan password wajib diisi." });
  }

  const admin = await Admin.findOne({ where: { username } });
  if (!admin) {
    return renderLogin(res, { errorMessage: "Login gagal." });
  }

  const isMatch = await bcrypt.compare(password, admin.passwordHash);
  if (!isMatch) {
    return renderLogin(res, { errorMessage: "Login gagal." });
  }

  const token = jwt.sign({ id: admin.id, username: admin.username }, env.jwt.secret, {
    expiresIn: env.jwt.expiresIn
  });

  res.cookie(env.jwt.cookieName, token, {
    httpOnly: true,
    sameSite: env.cookie.sameSite,
    secure: env.cookie.secure,
    maxAge: 7 * 24 * 60 * 60 * 1000
  });

  return res.redirect("/admin");
}

function postLogout(req, res) {
  res.clearCookie(env.jwt.cookieName);
  return res.redirect("/admin/login");
}

module.exports = {
  getLogin,
  postLogin,
  postLogout
};
