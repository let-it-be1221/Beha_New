import { useQuery } from '@tanstack/react-query';
import { ExternalLink, Shield } from 'lucide-react';
import { useAuth } from '../lib/auth';
import { API_ROOT_URL, http } from '../lib/api';
import { StatCard } from '../components/StatCard';
import { PendingList } from '../components/PendingList';
import { RecentActivity } from '../components/RecentActivity';
import type { DashboardStats } from '../types';

const PENDING_TITLES: Record<string, string> = {
  workflows: 'Pending Workflows',
  properties_to_verify: 'Properties Awaiting Verification',
  customers_to_approve: 'Customers Awaiting Approval',
  applicants_to_assign: 'Applicants Awaiting Assignment',
  customers_to_evaluate: 'Customers to Evaluate',
  my_recent_customers: 'My Recent Customers',
  none: 'Pending Items',
};

const PENDING_EMPTY: Record<string, string> = {
  workflows: 'No workflows in progress.',
  properties_to_verify: 'No properties awaiting verification.',
  customers_to_approve: 'No customers awaiting approval.',
  applicants_to_assign: 'No applicants awaiting assignment.',
  customers_to_evaluate: 'No customers to evaluate.',
  my_recent_customers: 'You have no customers yet.',
  none: 'Nothing pending.',
};

export function DashboardPage() {
  const { user } = useAuth();
  const { data, isLoading, error } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => http.get<DashboardStats>('/dashboard/stats').then((r) => r.data),
    refetchInterval: 30_000, // refresh every 30s for live dashboards
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12 text-gray-500">Loading dashboard…</div>
    );
  }

  if (error) {
    return (
      <div className="px-4 py-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
        Failed to load dashboard stats. Please try again.
      </div>
    );
  }

  if (!data) return null;

  const pendingTitle = PENDING_TITLES[data.pending.type] ?? 'Pending Items';
  const pendingEmpty = PENDING_EMPTY[data.pending.type] ?? 'Nothing pending.';

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between flex-wrap gap-4">
        <div>
          <h1 className="text-2xl font-bold">Welcome, {user?.username}</h1>
          <p className="text-gray-600">
            <span className="inline-flex items-center gap-1.5">
              <Shield className="h-4 w-4 text-beha-navy" />
              {data.role_label}
            </span>
            <span className="mx-2">·</span>
            Official ID: <span className="font-mono">{user?.official_id}</span>
            {user && user.level > 0 && (
              <>
                <span className="mx-2">·</span>
                Level {user.level}
              </>
            )}
          </p>
        </div>

        {/* Filament admin link for system_administrator */}
        {data.role === 'system_administrator' && (
          <a
            href={`${API_ROOT_URL}/admin`}
            target="_blank"
            rel="noreferrer"
            className="btn-secondary"
          >
            <ExternalLink className="h-4 w-4 mr-1 inline" />
            Filament Admin
          </a>
        )}
      </div>

      {/* Stats grid */}
      {data.stats.length > 0 && (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {data.stats.map((stat, i) => (
            <StatCard key={i} stat={stat} />
          ))}
        </div>
      )}

      {/* Pending + Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <PendingList
          title={pendingTitle}
          items={data.pending.items}
          emptyMessage={pendingEmpty}
        />
        <RecentActivity items={data.recent_activity} />
      </div>

      {/* Quick actions */}
      <div className="card p-5">
        <h2 className="font-semibold mb-3">Quick Actions</h2>
        <div className="flex flex-wrap gap-2 text-sm">
          {(data.role === 'team_member' || data.role === 'team_leader') && (
            <a href="/customers/new" className="btn-primary">+ New Customer</a>
          )}
          {data.role === 'generation_leader' && (
            <a href="/properties/new" className="btn-primary">+ New Property</a>
          )}
          <a href="/workflows" className="btn-secondary">View Workflows</a>
          <a href="/organization" className="btn-secondary">View Org Tree</a>
          <a href="/customers" className="btn-secondary">View Customers</a>
          <a href="/properties" className="btn-secondary">View Properties</a>
        </div>
      </div>
    </div>
  );
}
