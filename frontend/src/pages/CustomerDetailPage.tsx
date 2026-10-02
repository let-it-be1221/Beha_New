import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Customer } from '../types';

export function CustomerDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, error } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => http.get<Customer>(`/customers/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (error) return <p className="text-red-600">Failed to load customer.</p>;
  if (!data) return null;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <Link to="/customers" className="text-sm text-gray-500 hover:text-gray-700">← Back to customers</Link>
          <h1 className="text-2xl font-bold mt-1">{data.full_name}</h1>
          <p className="text-sm text-gray-500">
            Reference: <span className="font-mono">{data.reference_code}</span>
          </p>
        </div>
        <span className={`badge ${statusBadgeClass(data.status)}`}>{data.status_label}</span>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Contact</h2>
          <dl className="text-sm space-y-2">
            <Row label="Phone" value={data.phone} />
            <Row label="Email" value={data.email ?? '—'} />
            <Row label="Source" value={data.customer_source ?? '—'} />
          </dl>
        </div>
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Assignment</h2>
          <dl className="text-sm space-y-2">
            <Row label="Sales Agent" value={data.sales_agent?.username ?? '—'} />
            <Row label="Team ID" value={String(data.team_id)} />
            <Row label="Branch ID" value={String(data.branch_id)} />
            <Row label="Generation ID" value={String(data.generation_id)} />
          </dl>
        </div>
      </div>

      <div className="card p-5">
        <h2 className="font-semibold mb-3">Metadata</h2>
        <dl className="text-sm space-y-2">
          <Row label="Created" value={formatDate(data.created_at)} />
          <Row label="Updated" value={formatDate(data.updated_at)} />
        </dl>
      </div>
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between">
      <dt className="text-gray-500">{label}</dt>
      <dd className="font-medium text-gray-900">{value}</dd>
    </div>
  );
}
