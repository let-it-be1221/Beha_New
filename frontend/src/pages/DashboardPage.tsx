import { useAuth } from '../lib/auth';
import { formatPrice } from '../lib/utils';

export function DashboardPage() {
  const { user } = useAuth();
  if (!user) return null;

  const isTeamMember = user.level === 1 || user.level === 2;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Welcome, {user.username}</h1>
        <p className="text-gray-600">
          Official ID: <span className="font-mono">{user.official_id}</span>
          {user.level > 0 && <> · Level {user.level}</>}
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="My Customers" value="—" hint="Coming in Phase 6" />
        <StatCard label="My Sales" value={formatPrice(null)} hint="Coming in Phase 10" />
        <StatCard label="My Performance" value="—" hint="Coming in Phase 9" />
        <StatCard label="Available Properties" value="—" hint="Coming in Phase 7" />
      </div>

      <div className="card p-6">
        <h2 className="font-semibold mb-2">Phase 1 Status</h2>
        <p className="text-sm text-gray-600 mb-4">
          You're viewing the headless React SPA connected to the Laravel 12 API. Phase 1 is complete (architecture + scaffold + foundation code). Phase 2+ will flesh out the dashboard cards with live KPI data.
        </p>
        {!isTeamMember && (
          <a
            href="http://localhost:8000/admin"
            target="_blank"
            rel="noreferrer"
            className="btn-secondary"
          >
            Open Filament Admin →
          </a>
        )}
      </div>
    </div>
  );
}

function StatCard({ label, value, hint }: { label: string; value: string; hint?: string }) {
  return (
    <div className="card p-5">
      <p className="text-xs uppercase tracking-wide text-gray-500">{label}</p>
      <p className="text-2xl font-bold mt-1">{value}</p>
      {hint && <p className="text-xs text-gray-400 mt-1">{hint}</p>}
    </div>
  );
}
