/**
 * server.js
 * Express backend with authentication (email/password),
 * session-based login, product CRUD, payment integration,
 * and email functionality.
 *
 * NOTE: This Node.js server is optional. The main application
 * uses PHP backend. This server can be used for additional features.
 */

require('dotenv').config();
const express = require('express');
const session = require('express-session');
const helmet = require('helmet');
const bcrypt = require('bcrypt');
const cors = require('cors');
const sqlite3 = require('sqlite3').verbose();
const { open } = require('sqlite'); // sqlite wrapper for async/await
const nodemailer = require('nodemailer');
const Stripe = require('stripe');
const { v4: uuidv4 } = require('uuid');

const PORT = process.env.PORT || 3000;
const SESSION_SECRET = process.env.SESSION_SECRET || 'dev_secret_change_me';
const DB_FILE = process.env.DATABASE_FILE || './db.sqlite';
const STRIPE_SECRET_KEY = process.env.STRIPE_SECRET_KEY || '';
const stripe = STRIPE_SECRET_KEY ? Stripe(STRIPE_SECRET_KEY) : null;
const OWNER_EMAIL = process.env.OWNER_EMAIL || 'owner@example.com';

const app = express();

// Basic middleware
app.use(helmet());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
// CORS for local dev: allow frontend served from file:// or localhost: (adjust in production)
app.use(cors({
  origin: (origin, cb) => cb(null, true),
  credentials: true
}));

// Session middleware (MemoryStore - for production, use Redis or database-backed session store)
app.use(session({
  name: 'sid',
  secret: SESSION_SECRET,
  resave: false,
  saveUninitialized: false,
  cookie: {
    httpOnly: true,
    secure: false, // set true when using HTTPS
    sameSite: 'lax',
    maxAge: 1000 * 60 * 60 * 2 // 2 hours
  }
}));

// Async wrapper for sqlite
let db;
(async () => {
  db = await open({ filename: DB_FILE, driver: sqlite3.Database });

  // Create tables if not exist
  await db.run(`
    CREATE TABLE IF NOT EXISTS users (
      id TEXT PRIMARY KEY,
      email TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'admin',
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
  `);

  await db.run(`
    CREATE TABLE IF NOT EXISTS products (
      id TEXT PRIMARY KEY,
      title TEXT,
      description TEXT,
      price REAL,
      stock INTEGER,
      image TEXT,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
  `);

  await db.run(`
    CREATE TABLE IF NOT EXISTS orders (
      id TEXT PRIMARY KEY,
      user_email TEXT,
      payload TEXT,
      total REAL,
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
  `);

  // Optional: seed an admin if ADMIN_EMAIL & ADMIN_PASS env provided
  if (process.env.ADMIN_EMAIL && process.env.ADMIN_PASS) {
    const email = process.env.ADMIN_EMAIL;
    const password = process.env.ADMIN_PASS;
    const exists = await db.get('SELECT id FROM users WHERE email = ?', email);
    if (!exists) {
      const hash = await bcrypt.hash(password, 12);
      await db.run('INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, ?, ?)', [uuidv4(), email, hash, 'admin']);
      console.log('Seeded admin user:', email);
    }
  }
})();

// Helper middleware
function requireAuth(req, res, next) {
  if (req.session && req.session.user && req.session.user.email) return next();
  return res.status(401).json({ error: 'Unauthorized' });
}

// ---------- AUTH routes ----------
app.post('/api/register', async (req, res) => {
  try {
    const { email, password, role = 'admin' } = req.body;
    if (!email || !password) return res.status(400).json({ error: 'Missing email or password' });

    const existing = await db.get('SELECT * FROM users WHERE email = ?', email);
    if (existing) return res.status(409).json({ error: 'User already exists' });

    const password_hash = await bcrypt.hash(password, 12);
    const id = uuidv4();
    await db.run('INSERT INTO users (id, email, password_hash, role) VALUES (?, ?, ?, ?)', [id, email, password_hash, role]);
    return res.json({ ok: true, id, email });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Server error' });
  }
});

app.post('/api/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) return res.status(400).json({ error: 'Missing email or password' });

    const user = await db.get('SELECT id, email, password_hash, role FROM users WHERE email = ?', email);
    if (!user) return res.status(401).json({ error: 'Invalid credentials' });

    const ok = await bcrypt.compare(password, user.password_hash);
    if (!ok) return res.status(401).json({ error: 'Invalid credentials' });

    // Save minimal info into session
    req.session.user = { id: user.id, email: user.email, role: user.role };
    return res.json({ ok: true, email: user.email });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Server error' });
  }
});

app.post('/api/logout', (req, res) => {
  req.session.destroy(err => {
    if (err) return res.status(500).json({ error: 'Logout error' });
    res.clearCookie('sid');
    return res.json({ ok: true });
  });
});

app.get('/api/me', (req, res) => {
  if (!req.session.user) return res.json({ user: null });
  return res.json({ user: req.session.user });
});

// ---------- PRODUCTS API ----------
app.get('/api/products', async (req, res) => {
  const rows = await db.all('SELECT * FROM products ORDER BY created_at DESC');
  res.json({ products: rows });
});

app.post('/api/products', requireAuth, async (req, res) => {
  try {
    const { title, description, price = 0, stock = 0, image = '' } = req.body;
    const id = uuidv4();
    await db.run('INSERT INTO products (id, title, description, price, stock, image) VALUES (?,?,?,?,?,?)',
      [id, title, description, price, stock, image]);
    const p = await db.get('SELECT * FROM products WHERE id = ?', id);
    return res.json({ ok: true, product: p });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Server error' });
  }
});

app.put('/api/products/:id', requireAuth, async (req, res) => {
  try {
    const id = req.params.id;
    const { title, description, price, stock, image } = req.body;
    await db.run('UPDATE products SET title=?, description=?, price=?, stock=?, image=? WHERE id=?',
      [title, description, price, stock, image, id]);
    const p = await db.get('SELECT * FROM products WHERE id = ?', id);
    return res.json({ ok: true, product: p });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Server error' });
  }
});

app.delete('/api/products/:id', requireAuth, async (req, res) => {
  try {
    const id = req.params.id;
    await db.run('DELETE FROM products WHERE id = ?', id);
    return res.json({ ok: true });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Server error' });
  }
});

// ---------- PAYMENTS (Stripe) example ----------
app.post('/api/pay', async (req, res) => {
  if (!stripe) return res.status(500).json({ error: 'Stripe not configured' });
  try {
    const { amount, currency = 'usd', receipt_email } = req.body;
    if (!amount) return res.status(400).json({ error: 'Missing amount' });
    const paymentIntent = await stripe.paymentIntents.create({
      amount: Math.round(amount * 100),
      currency,
      receipt_email,
      metadata: { integration_check: 'accept_a_payment' },
    });
    return res.json({ ok: true, clientSecret: paymentIntent.client_secret });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Stripe error', details: err.message });
  }
});

// ---------- EMAIL (Nodemailer) example ----------
const transporter = nodemailer.createTransport({
  host: process.env.EMAIL_HOST || '',
  port: Number(process.env.EMAIL_PORT || 587),
  secure: false,
  auth: {
    user: process.env.EMAIL_USER || '',
    pass: process.env.EMAIL_PASS || ''
  }
});

app.post('/api/send-email', requireAuth, async (req, res) => {
  try {
    const { to, subject, html, text } = req.body;
    if (!to) return res.status(400).json({ error: 'Missing "to" address' });

    const info = await transporter.sendMail({
      from: process.env.EMAIL_USER || 'no-reply@example.com',
      to,
      subject: subject || 'Shoe Shop message',
      text: text || '',
      html: html || `<div>${text || ''}</div>`
    });
    return res.json({ ok: true, info });
  } catch (err) {
    console.error('Email error', err);
    return res.status(500).json({ error: 'Email send error', details: err.message });
  }
});

// ---------- ORDERS: Save order ----------
app.post('/api/orders', async (req, res) => {
  try {
    const { user_email, items, total } = req.body;
    const id = uuidv4();
    await db.run('INSERT INTO orders (id, user_email, payload, total) VALUES (?, ?, ?, ?)', [id, user_email, JSON.stringify(items), total]);
    return res.json({ ok: true, id });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ error: 'Server error' });
  }
});

app.get('/api/orders', requireAuth, async (req, res) => {
  const rows = await db.all('SELECT * FROM orders ORDER BY created_at DESC');
  res.json({ orders: rows });
});

app.use(express.static('public'));

app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
