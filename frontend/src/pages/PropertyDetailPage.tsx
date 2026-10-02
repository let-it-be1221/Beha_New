import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { useAuth } from '../lib/auth';
import { Timeline, type TimelineStep } from '../components/Timeline';
import { formatDate, formatPrice, statusBadgeClass } from '../lib/utils';
import type { Property, PropertyStatus } from '../types';
import { Building2, CheckCircle, Globe, IdCard, ArrowRight, Globe2 } from 'lucide-react';

export function PropertyDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();

  const { data, isLoading, error } = useQuery({
    queryKey: ['property', id],
    queryFn: () => http.get<Property>(`/properties/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (error) return <p className="text-red-600">Failed to load property.</p>;
  if (!data) return null;

  const timeline = buildTimeline(data);
  const userRoles = user?.roles ?? [];

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <Link to="/properties" className="text-sm text-gray-500 hover:text-gray-700">← Back to properties</Link>
          <h1 className="text-2xl font-bold mt-1">{data.name}</h1>
          <p className="text-sm text-gray-500 font-mono">{data.asset_code ?? 'No asset code assigned yet'}</p>
        </div>
        <span className={`badge ${statusBadgeClass(data.status)}`}>{data.status_label}</span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left column */}
        <div className="lg:col-span-2 space-y-4">
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
                <Row label="Size" value={data.size_sqm ? `${data.size_sqm} sqm` : '—'} />
              </dl>
            </div>
            <div className="card p-5">
              <h2 className="font-semibold mb-3">Location</h2>
              <p className="text-sm">{[data.city, data.region, data.country].filter(Boolean).join(', ') || '—'}</p>
            </div>
          </div>

          {/* Role-aware action panels */}
          {userRoles.includes('executive_officer') && data.status === 'pending_verification' && (
            <VerifyPanel property={data} />
          )}
          {userRoles.includes('record_officer') && data.status === 'pending_asset_coding' && (
            <AssignAssetCodePanel property={data} />
          )}
          {userRoles.includes('record_officer') && data.status === 'published' && (
            <PublishedPanel property={data} />
          )}
        </div>

        {/* Right column — timeline */}
        <div className="space-y-4">
          <Timeline steps={timeline} />
          <div className="card p-5">
            <h2 className="font-semibold mb-3">Lifecycle</h2>
            <dl className="text-sm space-y-1">
              <Row label="Registered by" value={data.registered_by?.username ?? '—'} />
              <Row label="Published" value={data.is_published ? formatDate(data.published_at) : 'Not published'} />
              <Row label="Created" value={formatDate(data.created_at)} />
            </dl>
          </div>
        </div>
      </div>
    </div>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  VERIFY PANEL (Executive Officer)
// ═════════════════════════════════════════════════════════════════════

function VerifyPanel({ property }: { property: Property }) {
  const queryClient = useQueryClient();
  const [comment, setComment] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState<string | null>(null);

  const act = async (decision: 'verify' | 'reject' | 'correction_required') => {
    setError(null);
    setSubmitting(decision);
    try {
      await http.post(`/properties/${property.id}/verify`, { decision, comment: comment || null });
      await queryClient.invalidateQueries({ queryKey: ['property', String(property.id)] });
      await queryClient.invalidateQueries({ queryKey: ['properties'] });
      setComment('');
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? `Failed to ${decision} property.`);
    } finally {
      setSubmitting(null);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <CheckCircle className="h-4 w-4 text-beha-navy" />
        Executive Officer Verification
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Review the property details and decide whether to forward to the Record Officer for asset coding.
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <textarea
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        className="input min-h-[80px] mb-3"
        placeholder="Add a comment (optional for verification, required for rejection)…"
      />
      <div className="flex flex-wrap gap-2">
        <button onClick={() => act('verify')} disabled={submitting !== null} className="btn-primary">
          {submitting === 'verify' ? 'Working…' : 'Verify & Forward'}
          <ArrowRight className="h-4 w-4 ml-1 inline" />
        </button>
        <button onClick={() => act('correction_required')} disabled={submitting !== null} className="btn-secondary">
          {submitting === 'correction_required' ? 'Working…' : 'Request Correction'}
        </button>
        <button onClick={() => act('reject')} disabled={submitting !== null} className="btn-danger">
          {submitting === 'reject' ? 'Working…' : 'Reject'}
        </button>
      </div>
    </div>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  ASSIGN ASSET CODE PANEL (Record Officer)
// ═════════════════════════════════════════════════════════════════════

function AssignAssetCodePanel({ property }: { property: Property }) {
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [assetCode, setAssetCode] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    setSubmitting(true);
    try {
      const res = await http.post<{ asset_code: string }>(`/properties/${property.id}/assign-asset-code`);
      setAssetCode(res.data.asset_code);
      await queryClient.invalidateQueries({ queryKey: ['property', String(property.id)] });
      await queryClient.invalidateQueries({ queryKey: ['properties'] });
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to assign asset code.');
    } finally {
      setSubmitting(false);
    }
  };

  const publish = async () => {
    setError(null);
    setSubmitting(true);
    try {
      await http.post(`/properties/${property.id}/publish`);
      await queryClient.invalidateQueries({ queryKey: ['property', String(property.id)] });
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to publish property.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <IdCard className="h-4 w-4 text-beha-navy" />
        Asset Coding & Publication
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Assign an immutable asset code (AST-YYYY-NNNNNN) and publish the property to the public portal.
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      {assetCode && (
        <div className="mb-3 px-3 py-2 rounded bg-green-50 border border-green-200 text-green-800 text-sm">
          ✓ Asset code assigned: <span className="font-mono font-bold">{assetCode}</span>
          <p className="text-xs mt-1">The code is now immutable. Audit-logged.</p>
        </div>
      )}
      <div className="flex flex-wrap gap-2">
        {!assetCode && (
          <button onClick={submit} disabled={submitting} className="btn-primary">
            {submitting ? 'Assigning…' : 'Assign Asset Code'}
          </button>
        )}
        {assetCode && (
          <button onClick={publish} disabled={submitting} className="btn-primary">
            {submitting ? 'Publishing…' : 'Publish to Public Portal'}
            <Globe className="h-4 w-4 ml-1 inline" />
          </button>
        )}
      </div>
    </div>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  PUBLISHED PANEL (Record Officer / Admin — unpublish safety valve)
// ═════════════════════════════════════════════════════════════════════

function PublishedPanel({ property }: { property: Property }) {
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const unpublish = async () => {
    if (!confirm(`Unpublish "${property.name}"? This will remove it from the public portal. Audit-logged.`)) return;
    setError(null);
    setSubmitting(true);
    try {
      await http.post(`/properties/${property.id}/unpublish`);
      await queryClient.invalidateQueries({ queryKey: ['property', String(property.id)] });
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to unpublish property.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <Globe2 className="h-4 w-4 text-green-600" />
        Published — Live on Public Portal
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        This property is publicly visible. Use the unpublish safety valve only if you need to remove it
        (e.g. data exposure concern per spec §31).
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <a
        href={`${import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1'}/properties/${property.id}`}
        target="_blank"
        rel="noreferrer"
        className="btn-secondary mr-2"
      >
        View on Public Portal
      </a>
      <button onClick={unpublish} disabled={submitting} className="btn-danger">
        {submitting ? 'Working…' : 'Unpublish (Safety Valve)'}
      </button>
    </div>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  HELPERS
// ═════════════════════════════════════════════════════════════════════

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between">
      <dt className="text-gray-500">{label}</dt>
      <dd className="font-medium text-gray-900">{value}</dd>
    </div>
  );
}

function buildTimeline(property: Property): TimelineStep[] {
  const statuses: PropertyStatus[] = [
    'draft',
    'pending_verification',
    'verified',
    'pending_asset_coding',
    'published',
  ];

  const currentIndex = statuses.indexOf(property.status);
  const isRejected = property.status === 'rejected';
  const isCorrection = property.status === 'correction_required';

  const labelMap: Record<string, string> = {
    draft: 'Draft Created',
    pending_verification: 'Pending Executive Officer Verification',
    verified: 'Verified by Executive Officer',
    pending_asset_coding: 'Pending Asset Code Assignment',
    published: 'Published — Live on Public Portal',
  };

  return statuses.map((status, i) => {
    let stepStatus: TimelineStep['status'];
    if (isRejected || isCorrection) {
      stepStatus = i < currentIndex ? 'completed' : 'pending';
    } else if (i < currentIndex) {
      stepStatus = 'completed';
    } else if (i === currentIndex) {
      stepStatus = property.status === 'published' ? 'completed' : 'current';
    } else {
      stepStatus = 'pending';
    }

    return {
      label: labelMap[status] ?? status,
      status: stepStatus,
      timestamp: i === 0 ? property.created_at : (i === currentIndex ? property.published_at ?? property.updated_at : undefined),
    };
  });
}
