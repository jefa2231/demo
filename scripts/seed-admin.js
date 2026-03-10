const bcrypt = require("bcryptjs");
const { Admin, sequelize } = require("../src/models");

async function run() {
  const username = process.argv[2] || process.env.ADMIN_USER || "admin";
  const password = process.argv[3] || process.env.ADMIN_PASS || "admin12345";

  if (!username || !password) {
    console.error("Usage: node scripts/seed-admin.js <username> <password>");
    process.exit(1);
  }

  try {
    await sequelize.authenticate();
    await sequelize.sync();

    const passwordHash = await bcrypt.hash(password, 10);

    const [admin, created] = await Admin.findOrCreate({
      where: { username },
      defaults: { passwordHash }
    });

    if (!created) {
      admin.passwordHash = passwordHash;
      await admin.save();
      console.log(`Admin '${username}' updated.`);
    } else {
      console.log(`Admin '${username}' created.`);
    }

    process.exit(0);
  } catch (error) {
    console.error("Seed failed:", error?.message || String(error));
    process.exit(1);
  }
}

run();
