# Beha Frontend — React 18 + TypeScript + Vite SPA

The user-facing half of the Beha Real Estate Management System. Consumes the Laravel 12 API at `/api/v1` via Sanctum cookie-based auth.

---

## Quick start

```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

The SPA runs on <http://localhost:5173>. It expects the backend to be running on <http://localhost:8000>.

---

## Environment

`frontend/.env.local`:

```env
VITE_API_URL=http://localhost:8000/api/v1
```

Adjust if your backend runs on a different port.

---

## Key commands

| Command | Purpose |
|---------|---------|
| `npm run dev` | Start Vite dev server on `:5173` (HMR enabled) |
| `npm run build` | Production build → `dist/` |
| `npm run preview` | Preview production build locally |
| `npm run lint` | ESLint (TS + TSX) |
| `npm run type-check` | TypeScript type-check (no emit) |

---

## Project structure

```
frontend/
├── public/
│   └── favicon.svg
├── src/
│   ├── components/
│   │   └── Layout.tsx              # Sidebar + topbar shell
│   ├── lib/
│   │   ├── api.ts                  # Axios instance with Sanctum cookie + CSRF
│   │   ├── auth.tsx                # AuthProvider context + useAuth() hook
│   │   ├── queryClient.ts          # TanStack Query client config
│   │   └── utils.ts                # cn(), formatPrice(), formatDate(), statusBadgeClass()
│   ├── pages/
│   │   ├── LoginPage.tsx
│   │   ├── ForcePasswordChangePage.tsx
│   │   ├── DashboardPage.tsx
│   │   ├── CustomersListPage.tsx
│   │   ├── CustomerDetailPage.tsx
│   │   ├── PropertiesListPage.tsx
│   │   ├── PropertyDetailPage.tsx
│   │   ├── ApplicantsListPage.tsx
│   │   ├── ApplicantDetailPage.tsx
│   │   ├── WorkflowsListPage.tsx
│   │   ├── WorkflowDetailPage.tsx
│   │   ├── OrganizationPage.tsx
│   │   └── NotFoundPage.tsx
│   ├── types/
│   │   └── index.ts                # TypeScript interfaces mirroring Laravel models
│   ├── App.tsx                     # Routes (public/protected/private)
│   ├── main.tsx                    # React entry + providers
│   └── index.css                   # Tailwind + custom components (.btn, .card, .badge, .input)
├── .env.example
├── .eslintrc.cjs
├── .gitignore
├── index.html
├── package.json
├── postcss.config.js
├── tailwind.config.js
├── tsconfig.json
├── tsconfig.node.json
└── vite.config.ts
```

---

## Sanctum cookie auth flow

The frontend uses Sanctum's SPA cookie-based auth (spec §37). The flow:

1. **Before login**: `fetchCsrfToken()` calls `GET http://localhost:8000/sanctum/csrf-cookie` to set the `XSRF-TOKEN` cookie.
2. **Login**: `POST /api/v1/auth/login` with `{ login, password, remember }`. Server sets the `beha_session` cookie.
3. **Subsequent requests**: Axios (configured with `withCredentials: true`) automatically sends both cookies. CSRF token is auto-included as the `X-XSRF-TOKEN` header on unsafe methods.
4. **Logout**: `POST /api/v1/auth/logout` destroys the session server-side.

For mobile / external API clients (future), use `POST /api/v1/auth/token` to get a bearer token. Attach it via `setBearerToken(token)` from `lib/api.ts`.

---

## Pages implemented

| Route | Page | Status |
|-------|------|--------|
| `/login` | LoginPage | ✅ Working |
| `/force-password-change` | ForcePasswordChangePage | ✅ Working |
| `/dashboard` | DashboardPage | ✅ Scaffold (Phase 2+) |
| `/customers` | CustomersListPage | ✅ Working — paginated table with status badges |
| `/customers/:id` | CustomerDetailPage | ✅ Working — contact + assignment + lifecycle |
| `/properties` | PropertiesListPage | ✅ Working — card grid with status badges |
| `/properties/:id` | PropertyDetailPage | ✅ Working — pricing + specs + lifecycle |
| `/applicants` | ApplicantsListPage | ✅ Working |
| `/applicants/:id` | ApplicantDetailPage | ✅ Working |
| `/workflows` | WorkflowsListPage | ✅ Working — list of workflows awaiting action |
| `/workflows/:id` | WorkflowDetailPage | ✅ Working — advance / reject panel + action timeline |
| `/organization` | OrganizationPage | ✅ Working — interactive tree |
| `*` | NotFoundPage | ✅ Working |

---

## Design system

Built on Tailwind CSS 3 with custom Beha tokens (defined in `tailwind.config.js`):

```js
colors: {
  beha: {
    navy: '#0F3A5F',        // primary
    'navy-600': '#145374',  // hover
    gold: '#C8A24B',        // accent
    'gold-soft': '#E8D7A0',
  }
},
fontFamily: {
  sans: ['Inter', 'system-ui', 'sans-serif'],
  heading: ['Lora', 'Georgia', 'serif'],
}
```

Custom utility classes in `src/index.css`:
- `.btn-primary` / `.btn-secondary` / `.btn-danger` — button variants
- `.card` — surface container
- `.input` — form input
- `.badge` + `.badge-{color}` — status pills

---

## Troubleshooting

See the **Troubleshooting** section in the root README for common issues (CORS, 401 unauthenticated, etc.).
