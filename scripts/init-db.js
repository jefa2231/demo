const mysql = require("mysql2/promise");
const env = require("../src/config/env");

async function run() {
  let connection;

  try {
    connection = await mysql.createConnection({
      host: env.db.host,
      port: env.db.port,
      user: env.db.user,
      password: env.db.pass,
      multipleStatements: false
    });

    await connection.query(
      `CREATE DATABASE IF NOT EXISTS \`${env.db.name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
    );

    console.log(`Database '${env.db.name}' ready.`);
    process.exit(0);
  } catch (error) {
    console.error("DB init failed:", error?.message || String(error));
    process.exit(1);
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

run();