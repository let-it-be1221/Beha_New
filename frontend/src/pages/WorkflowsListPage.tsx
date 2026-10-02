import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Paginated, WorkflowInstance } from '../types';

export function WorkflowsListPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['workflows'],
    queryFn: () => http.get<Paginated<WorkflowInstance>>('/workflows').then((r) => r.data),
  });

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">My Workflows</h1>
      <p className="text-sm text-gray-500">Workflows awaiting your action.</p>

      {isLoading && <p className="text-gray-500">Loading…</p>}
      {error && <p className="text-red-600">Failed to load workflows.</p>}

      {data && (
        <div className="space-y-2">
          {data.data.map((w) => (
            <Link
              key={w.id}
              to={`/workflows/${w.id}`}
              className="card p-4 flex items-center justify-between hover:shadow-lifted"
            >
              <div>
                <p className="font-semibold">
                  {w.workflow_type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                </p>
                <p className="text-xs text-gray-500">
                  Current step: <span className="font-mono">{w.current_step}</span>
                </p>
                <p className="text-xs text-gray-500">Created {formatDate(w.created_at)}</p>
              </div>
              <span className={`badge ${statusBadgeClass(w.status)}`}>{w.status}</span>
            </Link>
          ))}
          {data.data.length === 0 && (
            <div className="card p-8 text-center text-gray-500">No workflows awaiting your action.</div>
          )}
        </div>
      )}
    </div>
  );
}
