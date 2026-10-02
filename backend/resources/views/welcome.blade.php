<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Beha API</title>
</head>
<body style="font-family:system-ui;max-width:600px;margin:5rem auto;padding:2rem;color:#0F172A">
    <h1 style="color:#0F3A5F">Beha — Real Estate Management API</h1>
    <p>The backend is running. Available endpoints:</p>
    <ul style="line-height:1.8">
        <li><code>GET /api/v1/</code> — API health check</li>
        <li><code>GET /sanctum/csrf-cookie</code> — CSRF token for SPA auth</li>
        <li><code>POST /api/v1/auth/login</code> — SPA login (cookie)</li>
        <li><code>POST /api/v1/auth/token</code> — API token (mobile)</li>
        <li><code>GET /admin</code> — Filament admin panel (sys-admin only)</li>
    </ul>
    <p style="margin-top:2rem;color:#475569">Frontend SPA: <a href="http://localhost:5173">http://localhost:5173</a></p>
</body>
</html>
