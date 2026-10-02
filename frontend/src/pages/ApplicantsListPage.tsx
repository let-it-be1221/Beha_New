import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Applicant, Paginated } from '../types';

export function ApplicantsListPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['applicants'],
    queryFn: () => http.get<Paginated<Applicant>>('/applicants').then((r) => r.data),
  });

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Applicants</h1>

      {isLoading && <p className="text-gray-500">Loading…</p>}
      {error && <p className="text-red-600">Failed to load applicants.</p>}

      {data && (
        <div className="card overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
              <tr>
                <th className="px-5 py-3">Code</th>
                <th className="px-5 py-3">Name</th>
                <th className="px-5 py-3">Phone</th>
                <th className="px-5 py-3">Status</th>
                <th className="px-5 py-3">Applied</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data.data.map((a) => (
                <tr key={a.id} className="hover:bg-gray-50">
                  <td className="px-5 py-3 font-mono text-xs">
                    <Link to={`/applicants/${a.id}`} className="text-beha-navy hover:underline">
                      {a.application_code}
                    </Link>
                  </td>
                  <td className="px-5 py-3">{a.full_name}</td>
                  <td className="px-5 py-3">{a.phone}</td>
                  <td className="px-5 py-3">
                    <span className={`badge ${statusBadgeClass(a.status)}`}>{a.status_label}</span>
                  </td>
                  <td className="px-5 py-3 text-gray-500">{formatDate(a.created_at)}</td>
                </tr>
              ))}
              {data.data.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-5 py-8 text-center text-gray-500">No applicants found.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
