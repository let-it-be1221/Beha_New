import { formatDate } from '../lib/utils';
import type { DashboardActivityItem } from '../types';

interface Props {
  items: DashboardActivityItem[];
}

const SEVERITY_COLORS: Record<string, string> = {
  info: 'bg-blue-100 text-blue-700',
  warning: 'bg-amber-100 text-amber-700',
  critical: 'bg-red-100 text-red-700',
};

const CATEGORY_LABELS: Record<string, string> = {
  auth: 'Authentication',
  rbac: 'RBAC',
  workflow: 'Workflow',
  http: 'HTTP',
  security: 'Security',
  general: 'General',
};

export function RecentActivity({ items }: Props) {
  return (
    <div className="card">
      <div className="px-5 py-3 border-b border-gray-200">
        <h2 className="font-semibold">Recent Activity</h2>
        <p className="text-xs text-gray-500 mt-0.5">Last {items.length} events</p>
      </div>
      <ul className="divide-y divide-gray-100">
        {items.length === 0 ? (
          <li className="px-5 py-8 text-center text-sm text-gray-500">No recent activity.</li>
        ) : (
          items.map((item) => (
            <li key={item.id} className="px-5 py-3 flex items-start justify-between gap-3">
              <div className="min-w-0">
                <p className="text-sm font-medium text-gray-900">{item.action}</p>
                <p className="text-xs text-gray-500 mt-0.5">
                  by <span className="font-medium">{item.actor}</span>
                  {item.category && (
                    <>
                      {' · '}
                      <span className="text-gray-500">
                        {CATEGORY_LABELS[item.category] ?? item.category}
                      </span>
                    </>
                  )}
                </p>
              </div>
              <div className="text-right flex-shrink-0">
                <span
                  className={`inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded ${
                    SEVERITY_COLORS[item.severity] ?? SEVERITY_COLORS.info
                  }`}
                >
                  {item.severity}
                </span>
                <p className="text-xs text-gray-400 mt-1">{formatDate(item.created_at)}</p>
              </div>
            </li>
          ))
        )}
      </ul>
    </div>
  );
}
