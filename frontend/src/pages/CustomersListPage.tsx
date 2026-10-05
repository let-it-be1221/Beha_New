import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Customer, Paginated } from '../types';

export function CustomersListPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['customers'],
    queryFn: () => http.get<Paginated<Customer>>('/customers').then((r) => r.data),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Customers</h1>
        <Link to="/customers/new" className="btn-primary">+ New Customer</Link>
      </div>

      {isLoading && <p className="text-gray-500">Loading…</p>}
      {error && <p className="text-red-600">Failed to load customers.</p>}

      {data && (
        <div className="card overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
              <tr>
                <th className="px-5 py-3">Reference</th>
                <th className="px-5 py-3">Name</th>
                <th className="px-5 py-3">Phone</th>
                <th className="px-5 py-3">Status</th>
                <th className="px-5 py-3">Agent</th>
                <th className="px-5 py-3">Created</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data.data.map((c) => (
                <tr key={c.id} className="hover:bg-gray-50">
                  <td className="px-5 py-3 font-mono text-xs">
                    <Link to={`/customers/${c.id}`} className="text-beha-navy hover:underline">
                      {c.reference_code ?? <span className="text-gray-400">Pending</span>}
                    </Link>
                  </td>
                  <td className="px-5 py-3">{c.full_name}</td>
                  <td className="px-5 py-3">{c.phone}</td>
                  <td className="px-5 py-3">
                    <span className={`badge ${statusBadgeClass(c.status)}`}>{c.status_label}</span>
                  </td>
                  <td className="px-5 py-3">{c.sales_agent?.username ?? '—'}</td>
                  <td className="px-5 py-3 text-gray-500">{formatDate(c.created_at)}</td>
                </tr>
              ))}
              {data.data.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-5 py-8 text-center text-gray-500">
                    No customers found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
