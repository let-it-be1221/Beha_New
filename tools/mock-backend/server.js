/**
 * Beha Mock Backend — Node.js + Express + SQLite
 *
 * Mirrors the Laravel API routes + workflow logic so the frontend
 * can be tested end-to-end in a browser without PHP/MySQL.
 *
 * Sanctum cookie auth is simulated via a simple session table.
 */

const express = require('express');
const cors = require('cors');
const Database = require('better-sqlite3');
const bcrypt = require('bcryptjs');
const crypto = require('crypto');

const app = express();
const PORT = 8000;

// In-memory SQLite DB
const db = new Database(':memory:');
db.pragma('journal_mode = WAL');

// ─── Schema ───────────────────────────────────────────────────────────
db.exec(`
  CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    official_id TEXT UNIQUE,
    username TEXT UNIQUE,
    email TEXT UNIQUE,
    password TEXT,
    is_active INTEGER DEFAULT 1,
    level INTEGER DEFAULT 1,
    must_change_password INTEGER DEFAULT 0,
    roles TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reference_code TEXT UNIQUE,
    full_name TEXT,
    email TEXT,
    phone TEXT,
    national_id TEXT,
    customer_source TEXT,
    sales_agent_user_id INTEGER,
    team_id INTEGER,
    branch_id INTEGER,
    generation_id INTEGER,
    status TEXT DEFAULT 'pending_team_evaluation',
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE properties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_code TEXT UNIQUE,
    name TEXT,
    property_type TEXT,
    city TEXT,
    region TEXT,
    country TEXT,
    price REAL,
    bedrooms INTEGER,
    bathrooms INTEGER,
    size_sqm REAL,
    status TEXT DEFAULT 'pending_verification',
    is_published INTEGER DEFAULT 0,
    published_at TEXT,
    registered_by_user_id INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE applicants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_code TEXT UNIQUE,
    full_name TEXT,
    email TEXT,
    phone TEXT,
    national_id TEXT,
    status TEXT DEFAULT 'submitted',
    assigned_generation_id INTEGER,
    assigned_branch_id INTEGER,
    assigned_team_id INTEGER,
    generated_official_id TEXT,
    approved_at TEXT,
    rejected_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
  );
  CREATE TABLE generations (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, generation_number INTEGER UNIQUE, leader_user_id INTEGER, is_active INTEGER DEFAULT 1);
  CREATE TABLE branches (id INTEGER PRIMARY KEY AUTOINCREMENT, generation_id INTEGER, name TEXT, branch_number INTEGER, leader_user_id INTEGER, is_active INTEGER DEFAULT 1);
  CREATE TABLE teams (id INTEGER PRIMARY KEY AUTOINCREMENT, branch_id INTEGER, name TEXT, team_number INTEGER, leader_user_id INTEGER, is_active INTEGER DEFAULT 1);
  CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, entity_type TEXT, entity_id INTEGER, category TEXT, severity TEXT DEFAULT 'info', created_at TEXT DEFAULT CURRENT_TIMESTAMP);
  CREATE TABLE id_sequences (sequence_key TEXT UNIQUE, next_value INTEGER DEFAULT 1, prefix TEXT, padding INTEGER DEFAULT 6, last_used_at TEXT);
  CREATE TABLE customer_reviews (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INTEGER, reviewer_user_id INTEGER, review_stage TEXT, decision TEXT, comment TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
  CREATE TABLE property_verifications (id INTEGER PRIMARY KEY AUTOINCREMENT, property_id INTEGER, verifier_user_id INTEGER, decision TEXT, comment TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
  CREATE TABLE property_assets (id INTEGER PRIMARY KEY AUTOINCREMENT, property_id INTEGER UNIQUE, asset_code TEXT UNIQUE, assigned_by INTEGER, assigned_at TEXT DEFAULT CURRENT_TIMESTAMP);
  CREATE TABLE notifications (id TEXT PRIMARY KEY, user_id INTEGER, data TEXT, read_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
`);

// ─── Seed demo data ──────────────────────────────────────────────────
function seedData() {
  const pwd = bcrypt.hashSync('Demo1234!', 10);
  const users = [
    ['admin@beha.local', 'admin', 'BH000001', 0, 'system_administrator'],
    ['executive@beha.local', 'executive.officer', 'BH000002', 0, 'executive_officer'],
    ['record@beha.local', 'record.officer', 'BH000003', 0, 'record_officer'],
    ['finance@beha.local', 'finance.officer', 'BH000004', 0, 'finance_officer'],
    ['genleader@beha.local', 'gen.leader', 'BH000005', 5, 'generation_leader'],
    ['branchleader@beha.local', 'branch.leader', 'BH000006', 4, 'branch_leader'],
    ['teamleader@beha.local', 'team.leader', 'BH000007', 3, 'team_leader'],
    ['teammember@beha.local', 'team.member', 'BH000008', 1, 'team_member'],
  ];
  for (const [email, username, oid, level, role] of users) {
    db.prepare('INSERT INTO users (email, username, official_id, password, level, roles) VALUES (?,?,?,?,?,?)').run(email, username, oid, pwd, level, JSON.stringify([role]));
  }

  // Org hierarchy
  db.prepare('INSERT INTO generations (name, generation_number, leader_user_id) VALUES (?,?,?)').run('Generation 1', 1, 5);
  db.prepare('INSERT INTO branches (generation_id, name, branch_number, leader_user_id) VALUES (?,?,?,?)').run(1, 'Branch 1', 1, 6);
  db.prepare('INSERT INTO teams (branch_id, name, team_number, leader_user_id) VALUES (?,?,?,?)').run(1, 'Team 1', 1, 7);

  // Demo customers (3 pending team eval, 1 pending record approval, 3 registered)
  const customers = [
    ['Abebe Bekele', 'pending_team_evaluation', null],
    ['Sara Ahmed', 'pending_team_evaluation', null],
    ['Dawit Tadesse', 'pending_record_approval', null],
    ['Meron Girma', 'registered', 'CUS-2026-000001'],
    ['Yonas Hailu', 'registered', 'CUS-2026-000002'],
    ['Hanna Solomon', 'registered', 'CUS-2026-000003'],
  ];
  for (const [name, status, ref] of customers) {
    db.prepare(`INSERT INTO customers (full_name, email, phone, national_id, customer_source, sales_agent_user_id, team_id, branch_id, generation_id, status, reference_code)
      VALUES (?,?,?,?,?,?,?,?,?,?,?)`).run(name, name.toLowerCase().replace(' ', '.') + '@demo.local', '+2519' + Math.floor(10000000 + Math.random() * 89999999), 'ID-' + Math.floor(1000000 + Math.random() * 8999999), 'referral', 8, 1, 1, 1, status, ref);
  }

  // Demo properties
  const properties = [
    ['Sunset Apartments', 'apartment', 'Addis Ababa', 2500000, 'pending_verification', null],
    ['Piazza Heights', 'apartment', 'Addis Ababa', 1800000, 'pending_asset_coding', 'AST-2026-000001'],
    ['Lake View Condos', 'condo', 'Bahir Dar', 3200000, 'published', 'AST-2026-000002'],
    ['Mountain Ridge Houses', 'house', 'Gondar', 5500000, 'published', 'AST-2026-000003'],
  ];
  for (const [name, type, city, price, status, assetCode] of properties) {
    db.prepare(`INSERT INTO properties (name, property_type, city, country, price, bedrooms, bathrooms, size_sqm, status, is_published, asset_code, registered_by_user_id)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)`).run(name, type, city, 'Ethiopia', price, Math.floor(Math.random() * 4) + 1, Math.floor(Math.random() * 3) + 1, Math.floor(Math.random() * 200) + 60, status, status === 'published' ? 1 : 0, assetCode, 5);
  }

  // Demo applicants
  const applicants = [
    ['Applicant One', 'submitted'],
    ['Applicant Two', 'team_leader_screening'],
    ['Applicant Three', 'branch_assignment'],
    ['Applicant Four', 'record_verification'],
  ];
  let appSeq = 0;
  for (const [name, status] of applicants) {
    appSeq++;
    const code = 'APP-2026-' + String(appSeq).padStart(6, '0');
    db.prepare(`INSERT INTO applicants (application_code, full_name, email, phone, national_id, status)
      VALUES (?,?,?,?,?,?)`).run(code, name, name.toLowerCase().replace(' ', '.') + '@demo.local', '+2519' + Math.floor(10000000 + Math.random() * 89999999), 'ID-' + Math.floor(1000000 + Math.random() * 8999999), status);
  }

  // Sequences
  db.prepare('INSERT INTO id_sequences (sequence_key, next_value, prefix, padding) VALUES (?,?,?,?)').run('official_id', 9, 'BH', 6);
  db.prepare('INSERT INTO id_sequences (sequence_key, next_value, prefix, padding) VALUES (?,?,?,?)').run('cus:2026', 4, 'CUS', 6);
  db.prepare('INSERT INTO id_sequences (sequence_key, next_value, prefix, padding) VALUES (?,?,?,?)').run('ast:2026', 4, 'AST', 6);
  db.prepare('INSERT INTO id_sequences (sequence_key, next_value, prefix, padding) VALUES (?,?,?,?)').run('app:2026', 5, 'APP', 6);
  console.log('✓ Demo data seeded');
}
seedData();

// ─── Middleware ─────────────────────────────────────────────────────
app.use(cors({ origin: ['http://localhost:5173', 'http://localhost:3000', 'http://21.0.12.166:5173'], credentials: true }));
app.use(express.json());

// Session cookie parser (simplified Sanctum simulation)
app.use((req, res, next) => {
  const cookie = req.headers.cookie || '';
  const match = cookie.match(/beha_session=([^;]+)/);
  if (match) {
    const session = db.prepare('SELECT user_id FROM sessions WHERE id = ?').get(match[1]);
    if (session) {
      const user = db.prepare('SELECT * FROM users WHERE id = ?').get(session.user_id);
      if (user) {
        req.user = { ...user, roles: JSON.parse(user.roles) };
      }
    }
  }
  next();
});

function requireAuth(req, res, next) {
  if (!req.user) return res.status(401).json({ message: 'Unauthenticated.' });
  next();
}

function generateOfficialId() {
  const seq = db.prepare('SELECT * FROM id_sequences WHERE sequence_key = ?').get('official_id');
  const value = seq.next_value;
  db.prepare('UPDATE id_sequences SET next_value = ?, last_used_at = ? WHERE sequence_key = ?').run(value + 1, new Date().toISOString(), 'official_id');
  return seq.prefix + String(value).padStart(seq.padding, '0');
}

function generateRefCode(prefix, key) {
  const seq = db.prepare('SELECT * FROM id_sequences WHERE sequence_key = ?').get(key);
  const value = seq.next_value;
  db.prepare('UPDATE id_sequences SET next_value = ? WHERE sequence_key = ?').run(value + 1, key);
  return `${prefix}-2026-${String(value).padStart(6, '0')}`;
}

// ─── Auth routes ─────────────────────────────────────────────────────

// CSRF cookie endpoint (Sanctum)
app.get('/sanctum/csrf-cookie', (req, res) => {
  res.json({ message: 'CSRF cookie set' });
});

app.post('/api/v1/auth/login', (req, res) => {
  const { login, password } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE email = ? OR username = ?').get(login, login);
  if (!user || !bcrypt.compareSync(password, user.password)) {
    return res.status(422).json({ message: 'Invalid credentials', errors: { login: ['These credentials do not match our records.'] } });
  }
  if (!user.is_active) return res.status(403).json({ message: 'Account inactive' });

  const sessionId = crypto.randomUUID();
  db.prepare('INSERT INTO sessions (id, user_id) VALUES (?,?)').run(sessionId, user.id);
  res.setHeader('Set-Cookie', `beha_session=${sessionId}; Path=/; HttpOnly; SameSite=Lax`);
  const { password: _, ...userData } = user;
  res.json({
    message: 'Logged in',
    must_change_password: Boolean(user.must_change_password),
    user: { ...userData, roles: JSON.parse(user.roles), permissions: [] },
  });
});

app.post('/api/v1/auth/logout', requireAuth, (req, res) => {
  const cookie = req.headers.cookie || '';
  const match = cookie.match(/beha_session=([^;]+)/);
  if (match) db.prepare('DELETE FROM sessions WHERE id = ?').run(match[1]);
  res.json({ message: 'Logged out' });
});

app.get('/api/v1/auth/me', requireAuth, (req, res) => {
  const u = req.user;
  res.json({
    user: {
      id: u.id, official_id: u.official_id, username: u.username, email: u.email,
      is_active: Boolean(u.is_active), level: u.level, roles: u.roles, permissions: [],
      last_login_at: null, email_verified_at: null, created_at: u.created_at, updated_at: u.created_at,
    },
    must_change_password: Boolean(u.must_change_password),
  });
});

app.post('/api/v1/auth/change-password', requireAuth, (req, res) => {
  const { current_password, password } = req.body;
  if (!bcrypt.compareSync(current_password, req.user.password)) {
    return res.status(422).json({ errors: { current_password: ['Current password is incorrect.'] } });
  }
  db.prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?').run(bcrypt.hashSync(password, 10), req.user.id);
  res.json({ message: 'Password changed.' });
});

// ─── Dashboard ──────────────────────────────────────────────────────
app.get('/api/v1/dashboard/stats', requireAuth, (req, res) => {
  const role = req.user.roles[0];
  const stats = {
    system_administrator: [
      { label: 'Total Users', value: db.prepare('SELECT COUNT(*) as c FROM users').get().c, icon: 'users' },
      { label: 'Customers', value: db.prepare('SELECT COUNT(*) as c FROM customers').get().c, icon: 'users' },
      { label: 'Properties', value: db.prepare('SELECT COUNT(*) as c FROM properties').get().c, icon: 'building' },
      { label: 'Pending Workflows', value: 0, icon: 'workflow' },
    ],
    team_member: [
      { label: 'My Customers', value: db.prepare('SELECT COUNT(*) as c FROM customers WHERE sales_agent_user_id = ?').get(req.user.id).c, icon: 'users' },
      { label: 'My Level', value: req.user.level, icon: 'award' },
      { label: 'Available Properties', value: db.prepare('SELECT COUNT(*) as c FROM properties WHERE is_published = 1').get().c, icon: 'building' },
    ],
    team_leader: [
      { label: 'Pending Customer Evaluations', value: db.prepare("SELECT COUNT(*) as c FROM customers WHERE status = 'pending_team_evaluation'").get().c, icon: 'user-clock' },
      { label: 'Pending Applicant Screenings', value: db.prepare("SELECT COUNT(*) as c FROM applicants WHERE status = 'team_leader_screening'").get().c, icon: 'clipboard-list' },
    ],
    executive_officer: [
      { label: 'Properties Awaiting Verification', value: db.prepare("SELECT COUNT(*) as c FROM properties WHERE status = 'pending_verification'").get().c, icon: 'building' },
      { label: 'Published Properties', value: db.prepare('SELECT COUNT(*) as c FROM properties WHERE is_published = 1').get().c, icon: 'globe' },
    ],
    record_officer: [
      { label: 'Customers Awaiting Approval', value: db.prepare("SELECT COUNT(*) as c FROM customers WHERE status = 'pending_record_approval'").get().c, icon: 'user-clock' },
      { label: 'Properties Awaiting Asset Coding', value: db.prepare("SELECT COUNT(*) as c FROM properties WHERE status = 'pending_asset_coding'").get().c, icon: 'building' },
      { label: 'Pending ID Assignments', value: db.prepare("SELECT COUNT(*) as c FROM applicants WHERE status = 'record_verification'").get().c, icon: 'id-card' },
    ],
    generation_leader: [
      { label: 'Properties Registered', value: db.prepare('SELECT COUNT(*) as c FROM properties').get().c, icon: 'building' },
      { label: 'Published Properties', value: db.prepare('SELECT COUNT(*) as c FROM properties WHERE is_published = 1').get().c, icon: 'globe' },
    ],
    branch_leader: [
      { label: 'Pending Applicant Assignments', value: db.prepare("SELECT COUNT(*) as c FROM applicants WHERE status = 'branch_assignment'").get().c, icon: 'clipboard-list' },
    ],
    finance_officer: [
      { label: 'Registered Customers', value: db.prepare("SELECT COUNT(*) as c FROM customers WHERE status = 'registered'").get().c, icon: 'users' },
      { label: 'Total Property Value', value: db.prepare('SELECT SUM(price) as s FROM properties WHERE is_published = 1').get().s || 0, icon: 'dollar-sign', is_currency: true },
    ],
  };
  const userStats = stats[role] || stats.team_member;

  // Pending items
  let pending = { type: 'none', items: [] };
  if (role === 'team_leader') {
    pending = {
      type: 'customers_to_evaluate',
      items: db.prepare("SELECT id, full_name as title, reference_code as subtitle, phone as meta FROM customers WHERE status = 'pending_team_evaluation' LIMIT 8").all().map(c => ({ ...c, route: `/customers/${c.id}` })),
    };
  } else if (role === 'executive_officer') {
    pending = {
      type: 'properties_to_verify',
      items: db.prepare("SELECT id, name as title, city as subtitle, '' as meta FROM properties WHERE status = 'pending_verification' LIMIT 8").all().map(p => ({ ...p, route: `/properties/${p.id}` })),
    };
  } else if (role === 'record_officer') {
    pending = {
      type: 'customers_to_approve',
      items: db.prepare("SELECT id, full_name as title, reference_code as subtitle, phone as meta FROM customers WHERE status = 'pending_record_approval' LIMIT 8").all().map(c => ({ ...c, route: `/customers/${c.id}` })),
    };
  }

  // Recent activity
  const recent = db.prepare('SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 10').all().map(l => ({
    id: l.id, action: l.action, actor: l.username || 'system', category: l.category, severity: l.severity || 'info', created_at: l.created_at,
  }));

  const roleLabels = {
    system_administrator: 'System Administrator',
    executive_officer: 'Executive Officer',
    record_officer: 'Record Officer',
    finance_officer: 'Finance Officer',
    generation_leader: 'Generation Leader',
    branch_leader: 'Branch Leader',
    team_leader: 'Team Leader',
    team_member: req.user.level === 2 ? 'Senior Team Member' : 'Team Member',
  };

  res.json({ role, role_label: roleLabels[role] || role, stats: userStats, pending, recent_activity: recent });
});

// ─── Customers ───────────────────────────────────────────────────────
app.get('/api/v1/customers', requireAuth, (req, res) => {
  const rows = db.prepare(`SELECT c.*, u.username as agent_username FROM customers c LEFT JOIN users u ON c.sales_agent_user_id = u.id ORDER BY c.id DESC`).all();
  const data = rows.map(c => ({
    id: c.id,
    reference_code: c.reference_code,
    full_name: c.full_name,
    email: c.email,
    phone: c.phone,
    status: c.status,
    status_label: c.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    customer_source: c.customer_source,
    sales_agent: c.agent_username ? { id: c.sales_agent_user_id, official_id: 'BH000008', username: c.agent_username } : null,
    team_id: c.team_id, branch_id: c.branch_id, generation_id: c.generation_id,
    created_at: c.created_at, updated_at: c.updated_at,
  }));
  res.json({ data, current_page: 1, last_page: 1, per_page: 20, total: data.length, from: 1, to: data.length });
});

app.get('/api/v1/customers/:id', requireAuth, (req, res) => {
  const c = db.prepare('SELECT c.*, u.username as agent_username FROM customers c LEFT JOIN users u ON c.sales_agent_user_id = u.id WHERE c.id = ?').get(req.params.id);
  if (!c) return res.status(404).json({ message: 'Not found' });
  res.json({
    id: c.id, reference_code: c.reference_code, full_name: c.full_name, email: c.email, phone: c.phone,
    status: c.status, status_label: c.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    customer_source: c.customer_source,
    sales_agent: c.agent_username ? { id: c.sales_agent_user_id, official_id: 'BH000008', username: c.agent_username } : null,
    team_id: c.team_id, branch_id: c.branch_id, generation_id: c.generation_id,
    created_at: c.created_at, updated_at: c.updated_at,
  });
});

app.post('/api/v1/customers', requireAuth, (req, res) => {
  const { full_name, email, phone, national_id, customer_source, notes } = req.body;
  const result = db.prepare(`INSERT INTO customers (full_name, email, phone, national_id, customer_source, sales_agent_user_id, team_id, branch_id, generation_id, status)
    VALUES (?,?,?,?,?,?,?,?,?,?)`).run(full_name, email || null, phone, national_id || null, customer_source || null, req.user.id, 1, 1, 1, 'pending_team_evaluation');
  const c = db.prepare('SELECT * FROM customers WHERE id = ?').get(result.lastInsertRowid);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'customer.submit', 'Customer', c.id, 'workflow');
  res.json({
    id: c.id, reference_code: c.reference_code, full_name: c.full_name, email: c.email, phone: c.phone,
    status: c.status, status_label: 'Pending Team Evaluation',
    customer_source: c.customer_source,
    sales_agent: { id: req.user.id, official_id: req.user.official_id, username: req.user.username },
    team_id: 1, branch_id: 1, generation_id: 1, created_at: c.created_at, updated_at: c.updated_at,
  });
});

app.post('/api/v1/customers/:id/evaluate', requireAuth, (req, res) => {
  const { decision, comment } = req.body;
  const c = db.prepare('SELECT * FROM customers WHERE id = ?').get(req.params.id);
  if (!c) return res.status(404).json({ message: 'Not found' });
  if (!['submitted', 'pending_team_evaluation'].includes(c.status)) {
    return res.status(422).json({ message: `Cannot evaluate customer in status: ${c.status}` });
  }
  db.prepare('INSERT INTO customer_reviews (customer_id, reviewer_user_id, review_stage, decision, comment) VALUES (?,?,?,?,?)').run(c.id, req.user.id, 'team_leader', decision, comment || null);
  const newStatus = decision === 'approve' ? 'pending_record_approval' : decision === 'reject' ? 'rejected' : 'correction_required';
  db.prepare('UPDATE customers SET status = ?, updated_at = ? WHERE id = ?').run(newStatus, new Date().toISOString(), c.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, `customer.evaluate.${decision}`, 'Customer', c.id, 'workflow');
  const updated = db.prepare('SELECT * FROM customers WHERE id = ?').get(c.id);
  res.json({
    id: updated.id, reference_code: updated.reference_code, full_name: updated.full_name, email: updated.email, phone: updated.phone,
    status: updated.status, status_label: updated.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    customer_source: updated.customer_source,
    sales_agent: { id: req.user.id, official_id: req.user.official_id, username: req.user.username },
    team_id: 1, branch_id: 1, generation_id: 1, created_at: updated.created_at, updated_at: updated.updated_at,
  });
});

app.post('/api/v1/customers/:id/approve', requireAuth, (req, res) => {
  const c = db.prepare('SELECT * FROM customers WHERE id = ?').get(req.params.id);
  if (!c) return res.status(404).json({ message: 'Not found' });
  if (c.status !== 'pending_record_approval') {
    return res.status(422).json({ message: `Cannot approve customer in status: ${c.status}` });
  }
  // Simple duplicate check — look for matching national_id or phone
  const dup = db.prepare('SELECT * FROM customers WHERE id != ? AND (national_id = ? OR phone = ?) AND status = ?').get(c.id, c.national_id, c.phone, 'registered');
  if (dup) {
    return res.status(409).json({
      message: 'Potential duplicate customer found.',
      duplicates: [{ existing_customer_id: dup.id, existing_reference: dup.reference_code, existing_name: dup.full_name, match_field: 'national_id', match_score: 1.0 }],
    });
  }
  // No duplicate → generate reference code + register
  const refCode = generateRefCode('CUS', 'cus:2026');
  db.prepare('UPDATE customers SET reference_code = ?, status = ?, updated_at = ? WHERE id = ?').run(refCode, 'registered', new Date().toISOString(), c.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'customer.registered', 'Customer', c.id, 'workflow');
  const updated = db.prepare('SELECT * FROM customers WHERE id = ?').get(c.id);
  res.json({
    id: updated.id, reference_code: updated.reference_code, full_name: updated.full_name, email: updated.email, phone: updated.phone,
    status: updated.status, status_label: 'Registered', customer_source: updated.customer_source,
    sales_agent: { id: req.user.id, official_id: req.user.official_id, username: req.user.username },
    team_id: 1, branch_id: 1, generation_id: 1, created_at: updated.created_at, updated_at: updated.updated_at,
  });
});

app.post('/api/v1/customers/:id/reject', requireAuth, (req, res) => {
  const { reason } = req.body;
  db.prepare('UPDATE customers SET status = ?, notes = ?, updated_at = ? WHERE id = ?').run('rejected', reason, new Date().toISOString(), req.params.id);
  const updated = db.prepare('SELECT * FROM customers WHERE id = ?').get(req.params.id);
  res.json({
    id: updated.id, reference_code: updated.reference_code, full_name: updated.full_name, email: updated.email, phone: updated.phone,
    status: 'rejected', status_label: 'Rejected', customer_source: updated.customer_source,
    sales_agent: null, team_id: 1, branch_id: 1, generation_id: 1, created_at: updated.created_at, updated_at: updated.updated_at,
  });
});

app.post('/api/v1/customers/:id/duplicates/:dupId/resolve', requireAuth, (req, res) => {
  const { action } = req.body;
  if (action === 'created_new') {
    const refCode = generateRefCode('CUS', 'cus:2026');
    db.prepare('UPDATE customers SET reference_code = ?, status = ?, updated_at = ? WHERE id = ?').run(refCode, 'registered', new Date().toISOString(), req.params.id);
  } else {
    // kept_existing — mark as registered with existing ref (simplified)
    db.prepare('UPDATE customers SET status = ?, updated_at = ? WHERE id = ?').run('registered', new Date().toISOString(), req.params.id);
  }
  const updated = db.prepare('SELECT * FROM customers WHERE id = ?').get(req.params.id);
  res.json({
    id: updated.id, reference_code: updated.reference_code, full_name: updated.full_name, email: updated.email, phone: updated.phone,
    status: 'registered', status_label: 'Registered', customer_source: updated.customer_source,
    sales_agent: null, team_id: 1, branch_id: 1, generation_id: 1, created_at: updated.created_at, updated_at: updated.updated_at,
  });
});

// ─── Properties ─────────────────────────────────────────────────────
app.get('/api/v1/properties', requireAuth, (req, res) => {
  const rows = db.prepare('SELECT * FROM properties ORDER BY id DESC').all();
  const data = rows.map(p => ({
    id: p.id, asset_code: p.asset_code, name: p.name, property_type: p.property_type,
    city: p.city, region: p.region, country: p.country, price: p.price,
    bedrooms: p.bedrooms, bathrooms: p.bathrooms, size_sqm: p.size_sqm,
    status: p.status, status_label: p.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    is_published: Boolean(p.is_published), published_at: p.published_at,
    primary_image: null, amenities: null,
    registered_by: { id: p.registered_by_user_id, official_id: 'BH000005', username: 'gen.leader' },
    created_at: p.created_at,
  }));
  res.json({ data, current_page: 1, last_page: 1, per_page: 20, total: data.length, from: 1, to: data.length });
});

app.get('/api/v1/properties/:id', requireAuth, (req, res) => {
  const p = db.prepare('SELECT * FROM properties WHERE id = ?').get(req.params.id);
  if (!p) return res.status(404).json({ message: 'Not found' });
  res.json({
    id: p.id, asset_code: p.asset_code, name: p.name, property_type: p.property_type,
    city: p.city, region: p.region, country: p.country, price: p.price,
    bedrooms: p.bedrooms, bathrooms: p.bathrooms, size_sqm: p.size_sqm,
    status: p.status, status_label: p.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    is_published: Boolean(p.is_published), published_at: p.published_at,
    primary_image: null, amenities: null,
    registered_by: { id: p.registered_by_user_id, official_id: 'BH000005', username: 'gen.leader' },
    created_at: p.created_at,
  });
});

app.post('/api/v1/properties', requireAuth, (req, res) => {
  const { name, property_type, city, region, country, price, bedrooms, bathrooms, size_sqm, description } = req.body;
  const result = db.prepare(`INSERT INTO properties (name, property_type, city, region, country, price, bedrooms, bathrooms, size_sqm, status, registered_by_user_id)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)`).run(name, property_type, city || null, region || null, country || null, price, bedrooms || null, bathrooms || null, size_sqm || null, 'pending_verification', req.user.id);
  const p = db.prepare('SELECT * FROM properties WHERE id = ?').get(result.lastInsertRowid);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'property.submit', 'Property', p.id, 'workflow');
  res.json({
    id: p.id, asset_code: null, name: p.name, property_type: p.property_type,
    city: p.city, region: p.region, country: p.country, price: p.price,
    bedrooms: p.bedrooms, bathrooms: p.bathrooms, size_sqm: p.size_sqm,
    status: 'pending_verification', status_label: 'Pending Verification',
    is_published: false, published_at: null, primary_image: null, amenities: null,
    registered_by: { id: req.user.id, official_id: req.user.official_id, username: req.user.username },
    created_at: p.created_at,
  });
});

app.post('/api/v1/properties/:id/verify', requireAuth, (req, res) => {
  const { decision, comment } = req.body;
  const p = db.prepare('SELECT * FROM properties WHERE id = ?').get(req.params.id);
  if (!p) return res.status(404).json({ message: 'Not found' });
  if (p.status !== 'pending_verification') {
    return res.status(422).json({ message: `Cannot verify property in status: ${p.status}` });
  }
  db.prepare('INSERT INTO property_verifications (property_id, verifier_user_id, decision, comment) VALUES (?,?,?,?)').run(p.id, req.user.id, decision, comment || null);
  const newStatus = decision === 'verify' ? 'pending_asset_coding' : decision === 'reject' ? 'rejected' : 'correction_required';
  db.prepare('UPDATE properties SET status = ?, updated_at = ? WHERE id = ?').run(newStatus, new Date().toISOString(), p.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, `property.verify.${decision}`, 'Property', p.id, 'workflow');
  const updated = db.prepare('SELECT * FROM properties WHERE id = ?').get(p.id);
  res.json({
    id: updated.id, asset_code: updated.asset_code, name: updated.name, property_type: updated.property_type,
    city: updated.city, region: updated.region, country: updated.country, price: updated.price,
    bedrooms: updated.bedrooms, bathrooms: updated.bathrooms, size_sqm: updated.size_sqm,
    status: updated.status, status_label: updated.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    is_published: Boolean(updated.is_published), published_at: updated.published_at,
    primary_image: null, amenities: null,
    registered_by: { id: updated.registered_by_user_id, official_id: 'BH000005', username: 'gen.leader' },
    created_at: updated.created_at,
  });
});

app.post('/api/v1/properties/:id/assign-asset-code', requireAuth, (req, res) => {
  const p = db.prepare('SELECT * FROM properties WHERE id = ?').get(req.params.id);
  if (!p) return res.status(404).json({ message: 'Not found' });
  if (p.status !== 'pending_asset_coding') {
    return res.status(422).json({ message: `Cannot assign asset code in status: ${p.status}` });
  }
  const assetCode = generateRefCode('AST', 'ast:2026');
  db.prepare('INSERT INTO property_assets (property_id, asset_code, assigned_by) VALUES (?,?,?)').run(p.id, assetCode, req.user.id);
  db.prepare('UPDATE properties SET asset_code = ?, status = ?, updated_at = ? WHERE id = ?').run(assetCode, 'published', new Date().toISOString(), p.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'property.assign_asset_code', 'Property', p.id, 'identity');
  res.json({ message: 'Asset code assigned.', asset_code: assetCode, property: { id: p.id, asset_code: assetCode, status: 'published' } });
});

app.post('/api/v1/properties/:id/publish', requireAuth, (req, res) => {
  const p = db.prepare('SELECT * FROM properties WHERE id = ?').get(req.params.id);
  if (!p) return res.status(404).json({ message: 'Not found' });
  if (!p.asset_code) return res.status(422).json({ message: 'Property must have an asset code before publication.' });
  db.prepare('UPDATE properties SET is_published = 1, published_at = ?, status = ?, updated_at = ? WHERE id = ?').run(new Date().toISOString(), 'published', new Date().toISOString(), p.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'property.publish', 'Property', p.id, 'workflow');
  const updated = db.prepare('SELECT * FROM properties WHERE id = ?').get(p.id);
  res.json({
    id: updated.id, asset_code: updated.asset_code, name: updated.name, property_type: updated.property_type,
    city: updated.city, region: updated.region, country: updated.country, price: updated.price,
    status: 'published', status_label: 'Published', is_published: true, published_at: updated.published_at,
    registered_by: { id: updated.registered_by_user_id, official_id: 'BH000005', username: 'gen.leader' },
    created_at: updated.created_at,
  });
});

// ─── Applicants ─────────────────────────────────────────────────────
app.get('/api/v1/applicants', requireAuth, (req, res) => {
  const rows = db.prepare('SELECT * FROM applicants ORDER BY id DESC').all();
  const data = rows.map(a => ({
    id: a.id, application_code: a.application_code, full_name: a.full_name, email: a.email, phone: a.phone,
    status: a.status, status_label: a.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    assigned_generation: null, assigned_branch: null, assigned_team: null,
    approved_at: a.approved_at, rejected_at: a.rejected_at, rejection_reason: null,
    official_id: a.generated_official_id, created_at: a.created_at,
  }));
  res.json({ data, current_page: 1, last_page: 1, per_page: 20, total: data.length, from: 1, to: data.length });
});

app.get('/api/v1/applicants/:id', requireAuth, (req, res) => {
  const a = db.prepare('SELECT * FROM applicants WHERE id = ?').get(req.params.id);
  if (!a) return res.status(404).json({ message: 'Not found' });
  res.json({
    id: a.id, application_code: a.application_code, full_name: a.full_name, email: a.email, phone: a.phone,
    status: a.status, status_label: a.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    assigned_generation: null, assigned_branch: null, assigned_team: null,
    approved_at: a.approved_at, rejected_at: a.rejected_at, rejection_reason: null,
    official_id: a.generated_official_id, created_at: a.created_at,
  });
});

app.post('/api/v1/applicants/apply', (req, res) => {
  const { full_name, email, phone, national_id, address } = req.body;
  const seq = db.prepare('SELECT * FROM id_sequences WHERE sequence_key = ?').get('app:2026');
  const value = seq.next_value;
  db.prepare('UPDATE id_sequences SET next_value = ? WHERE sequence_key = ?').run(value + 1, 'app:2026');
  const code = `APP-2026-${String(value).padStart(6, '0')}`;
  const result = db.prepare('INSERT INTO applicants (application_code, full_name, email, phone, national_id, status) VALUES (?,?,?,?,?,?)').run(code, full_name, email, phone, national_id || null, 'submitted');
  res.status(201).json({
    message: 'Application submitted. A Team Leader will be in touch shortly.',
    applicant: { id: result.lastInsertRowid, application_code: code, full_name, email, phone, status: 'submitted', status_label: 'Submitted', created_at: new Date().toISOString() },
  });
});

app.post('/api/v1/applicants/:id/screen', requireAuth, (req, res) => {
  const { decision, comment } = req.body;
  const a = db.prepare('SELECT * FROM applicants WHERE id = ?').get(req.params.id);
  if (!a) return res.status(404).json({ message: 'Not found' });
  const newStatus = decision === 'approve' ? 'branch_assignment' : decision === 'reject' ? 'rejected' : 'team_leader_screening';
  db.prepare('UPDATE applicants SET status = ? WHERE id = ?').run(newStatus, a.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, `applicant.screen.${decision}`, 'Applicant', a.id, 'workflow');
  const updated = db.prepare('SELECT * FROM applicants WHERE id = ?').get(a.id);
  res.json({
    id: updated.id, application_code: updated.application_code, full_name: updated.full_name, email: updated.email, phone: updated.phone,
    status: updated.status, status_label: updated.status.split('_').map(w => w[0].toUpperCase() + w.slice(1)).join(' '),
    assigned_generation: null, assigned_branch: null, assigned_team: null,
    approved_at: null, rejected_at: null, rejection_reason: null, official_id: updated.generated_official_id, created_at: updated.created_at,
  });
});

app.post('/api/v1/applicants/:id/assign', requireAuth, (req, res) => {
  const { generation_id, branch_id, team_id } = req.body;
  db.prepare('UPDATE applicants SET status = ?, assigned_generation_id = ?, assigned_branch_id = ?, assigned_team_id = ? WHERE id = ?').run('record_verification', generation_id, branch_id, team_id, req.params.id);
  const updated = db.prepare('SELECT * FROM applicants WHERE id = ?').get(req.params.id);
  res.json({
    id: updated.id, application_code: updated.application_code, full_name: updated.full_name, email: updated.email, phone: updated.phone,
    status: 'record_verification', status_label: 'Record Verification',
    assigned_generation: { id: generation_id, name: 'Generation 1', generation_number: 1 },
    assigned_branch: { id: branch_id, name: 'Branch 1', branch_number: 1 },
    assigned_team: { id: team_id, name: 'Team 1', team_number: 1 },
    approved_at: null, rejected_at: null, rejection_reason: null, official_id: updated.generated_official_id, created_at: updated.created_at,
  });
});

app.post('/api/v1/applicants/:id/generate-ids', requireAuth, (req, res) => {
  const officialId = generateOfficialId();
  db.prepare('UPDATE applicants SET status = ?, generated_official_id = ? WHERE id = ?').run('account_creation', officialId, req.params.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'applicant.generate_ids', 'Applicant', req.params.id, 'identity');
  res.json({ message: 'IDs generated.', official_id: officialId });
});

app.post('/api/v1/applicants/:id/create-account', requireAuth, (req, res) => {
  const { username, email } = req.body;
  const a = db.prepare('SELECT * FROM applicants WHERE id = ?').get(req.params.id);
  if (!a.generated_official_id) return res.status(422).json({ message: 'IDs must be generated first.' });
  const tempPassword = crypto.randomBytes(18).toString('base64').slice(0, 24);
  db.prepare('INSERT INTO users (email, username, official_id, password, level, roles, must_change_password) VALUES (?,?,?,?,?,?,?)').run(email, username, a.generated_official_id, bcrypt.hashSync(tempPassword, 10), 1, JSON.stringify(['team_member']), 1);
  db.prepare('UPDATE applicants SET status = ?, approved_at = ? WHERE id = ?').run('approved', new Date().toISOString(), a.id);
  db.prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, category) VALUES (?,?,?,?,?)').run(req.user.id, 'applicant.account_created', 'Applicant', a.id, 'workflow');
  res.status(201).json({ message: 'Account created.', temp_password: tempPassword, user: { id: db.prepare('SELECT last_insert_rowid() as id').get().id, username, email, official_id: a.generated_official_id } });
});

// ─── Workflows ──────────────────────────────────────────────────────
app.get('/api/v1/workflows', requireAuth, (req, res) => {
  res.json({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 });
});

// ─── Organization ───────────────────────────────────────────────────
app.get('/api/v1/generations', requireAuth, (req, res) => {
  const gens = db.prepare('SELECT * FROM generations').all();
  res.json({ data: gens.map(g => ({ id: g.id, name: g.name, generation_number: g.generation_number, is_active: Boolean(g.is_active), leader_user_id: g.leader_user_id, branches: [] })) });
});

app.get('/api/v1/branches', requireAuth, (req, res) => {
  const branches = db.prepare('SELECT * FROM branches').all();
  res.json({ data: branches.map(b => ({ id: b.id, generation_id: b.generation_id, name: b.name, branch_number: b.branch_number, is_active: Boolean(b.is_active), leader_user_id: b.leader_user_id, teams: [] })) });
});

app.get('/api/v1/teams', requireAuth, (req, res) => {
  const teams = db.prepare('SELECT * FROM teams').all();
  res.json({ data: teams.map(t => ({ id: t.id, branch_id: t.branch_id, name: t.name, team_number: t.team_number, is_active: Boolean(t.is_active), leader_user_id: t.leader_user_id, members: [] })) });
});

app.get('/api/v1/organization/tree', requireAuth, (req, res) => {
  const gens = db.prepare('SELECT * FROM generations').all();
  const branches = db.prepare('SELECT * FROM branches').all();
  const teams = db.prepare('SELECT * FROM teams').all();
  const tree = gens.map(g => ({
    ...g, is_active: Boolean(g.is_active),
    branches: branches.filter(b => b.generation_id === g.id).map(b => ({
      ...b, is_active: Boolean(b.is_active),
      teams: teams.filter(t => t.branch_id === b.id).map(t => ({ ...t, is_active: Boolean(t.is_active), members: [] })),
    })),
  }));
  res.json({ generations: tree });
});

// ─── Notifications ──────────────────────────────────────────────────
app.get('/api/v1/notifications', requireAuth, (req, res) => {
  res.json({ notifications: { data: [] }, unread_count: 0 });
});

// ─── Start ──────────────────────────────────────────────────────────
app.listen(PORT, '0.0.0.0', () => {
  console.log(`✓ Beha mock backend running at http://localhost:${PORT}`);
  console.log(`  Demo users (password: Demo1234!):`);
  console.log(`    admin@beha.local / executive@beha.local / record@beha.local`);
  console.log(`    finance@beha.local / genleader@beha.local / branchleader@beha.local`);
  console.log(`    teamleader@beha.local / teammember@beha.local`);
});
