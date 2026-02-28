import mysql from "mysql2/promise";

let pool: mysql.Pool | null = null;

export function getPool(): mysql.Pool {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.MYSQL_SERVER_NAME || "localhost",
      user: process.env.MYSQL_SERVER_USER_NAME || "root",
      password: process.env.MYSQL_ROOT_PASSWORD || "",
      database: process.env.MYSQL_DATABASE_NAME || "swuonline",
      waitForConnections: true,
      connectionLimit: 5,
    });
  }
  return pool;
}

export async function closePool(): Promise<void> {
  if (pool) {
    await pool.end();
    pool = null;
  }
}
