const { sequelize } = require("../src/models");

async function run() {
  const force = process.argv.includes("--force");

  try {
    await sequelize.authenticate();
    await sequelize.sync({ alter: !force, force });
    console.log(`Migration done. force=${force}`);
    process.exit(0);
  } catch (error) {
    console.error("Migration failed:", error?.message || String(error));
    process.exit(1);
  }
}

run();
