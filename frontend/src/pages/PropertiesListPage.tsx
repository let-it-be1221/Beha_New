import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { http } from '../lib/api';
import { formatPrice, statusBadgeClass } from '../lib/utils';
import type { Paginated, Property } from '../types';

export function PropertiesListPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['properties'],
    queryFn: () => http.get<Paginated<Property>>('/properties').then((r) => r.data),
  });

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Properties</h1>
        <Link to="/properties/new" className="btn-primary">+ New Property</Link>
      </div>

      {isLoading && <p className="text-gray-500">Loading…</p>}
      {error && <p className="text-red-600">Failed to load properties.</p>}

      {data && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {data.data.map((p) => (
            <Link key={p.id} to={`/properties/${p.id}`} className="card overflow-hidden hover:shadow-lifted">
              <div className="aspect-video bg-gray-200" />
              <div className="p-4">
                <div className="flex items-center justify-between mb-2">
                  <span className={`badge ${statusBadgeClass(p.status)}`}>{p.status_label}</span>
                  <span className="text-xs text-gray-500 font-mono">{p.asset_code ?? 'no-asset-code'}</span>
                </div>
                <h3 className="font-semibold text-gray-900">{p.name}</h3>
                <p className="text-sm text-gray-500">{[p.city, p.region, p.country].filter(Boolean).join(', ') || '—'}</p>
                <p className="text-lg font-bold text-beha-navy mt-2">{formatPrice(p.price)}</p>
              </div>
            </Link>
          ))}
          {data.data.length === 0 && (
            <div className="card p-8 text-center text-gray-500 col-span-full">
              No properties found.
            </div>
          )}
        </div>
      )}
    </div>
  );
}
