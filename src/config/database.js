const { Sequelize } = require("sequelize");
const env = require("./env");

const sequelize = new Sequelize(env.db.name, env.db.user, env.db.pass, {
  host: env.db.host,
  port: env.db.port,
  dialect: "mysql",
  logging: env.isProd ? false : false,
  define: {
    underscored: true
  },
  timezone: "+00:00"
});

module.exports = sequelize;