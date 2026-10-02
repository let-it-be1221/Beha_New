import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Applicant } from '../types';

export function ApplicantDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, error } = useQuery({
    queryKey: ['applicant', id],
    queryFn: () => http.get<Applicant>(`/applicants/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (error) return <p className="text-red-600">Failed to load applicant.</p>;
  if (!data) return null;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <Link to="/applicants" className="text-sm text-gray-500 hover:text-gray-700">← Back to applicants</Link>
          <h1 className="text-2xl font-bold mt-1">{data.full_name}</h1>
          <p className="text-sm text-gray-500 font-mono">{data.application_code}</p>
        </div>
        <span className={`badge ${statusBadgeClass(data.status)}`}>{data.status_label}</span>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Contact</h2>
          <dl className="text-sm space-y-2">
            <Row label="Email" value={data.email} />
            <Row label="Phone" value={data.phone} />
          </dl>
        </div>
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Assignment</h2>
          <dl className="text-sm space-y-2">
            <Row label="Generation" value={data.assigned_generation?.name ?? '—'} />
            <Row label="Branch" value={data.assigned_branch?.name ?? '—'} />
            <Row label="Team" value={data.assigned_team?.name ?? '—'} />
          </dl>
        </div>
      </div>

      <div className="card p-5">
        <h2 className="font-semibold mb-3">Lifecycle</h2>
        <dl className="text-sm space-y-2">
          <Row label="Applied" value={formatDate(data.created_at)} />
          <Row label="Approved" value={formatDate(data.approved_at)} />
          <Row label="Rejected" value={formatDate(data.rejected_at)} />
          {data.rejection_reason && <Row label="Reason" value={data.rejection_reason} />}
          {data.official_id && <Row label="Official ID" value={data.official_id} />}
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
