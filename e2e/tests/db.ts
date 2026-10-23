import mysql from 'mysql2/promise';

export async function resetOrders(): Promise<void> {
  const db = await mysql.createConnection({
    host: process.env.DB_HOST ?? 'db',
    user: 'app',
    password: 'app',
    database: 'app',
  });
  await db.query('DELETE FROM order_item');
  await db.query('DELETE FROM orders');
  await db.end();
}
