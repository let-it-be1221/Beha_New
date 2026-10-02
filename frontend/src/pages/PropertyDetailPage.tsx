import { useQuery } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate, formatPrice, statusBadgeClass } from '../lib/utils';
import type { Property } from '../types';

export function PropertyDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, error } = useQuery({
    queryKey: ['property', id],
    queryFn: () => http.get<Property>(`/properties/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (error) return <p className="text-red-600">Failed to load property.</p>;
  if (!data) return null;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <Link to="/properties" className="text-sm text-gray-500 hover:text-gray-700">← Back to properties</Link>
          <h1 className="text-2xl font-bold mt-1">{data.name}</h1>
          <p className="text-sm text-gray-500 font-mono">{data.asset_code ?? 'No asset code assigned yet'}</p>
        </div>
        <span className={`badge ${statusBadgeClass(data.status)}`}>{data.status_label}</span>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Pricing</h2>
          <p className="text-3xl font-bold text-beha-navy">{formatPrice(data.price)}</p>
          <p className="text-sm text-gray-500 mt-1">{data.property_type}</p>
        </div>
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Specifications</h2>
          <dl className="text-sm space-y-1">
            <Row label="Bedrooms" value={String(data.bedrooms ?? '—')} />
            <Row label="Bathrooms" value={String(data.bathrooms ?? '—')} />
            <Row label="Size (sqm)" value={String(data.size_sqm ?? '—')} />
          </dl>
        </div>
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Location</h2>
          <p className="text-sm">
            {[data.city, data.region, data.country].filter(Boolean).join(', ') || '—'}
          </p>
        </div>
      </div>

      <div className="card p-5">
        <h2 className="font-semibold mb-3">Lifecycle</h2>
        <dl className="text-sm space-y-1">
          <Row label="Registered by" value={data.registered_by?.username ?? '—'} />
          <Row label="Published" value={data.is_published ? formatDate(data.published_at) : 'Not published'} />
          <Row label="Created" value={formatDate(data.created_at)} />
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
