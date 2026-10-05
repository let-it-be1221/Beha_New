import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { useAuth } from '../lib/auth';
import { Timeline, type TimelineStep } from '../components/Timeline';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Customer, CustomerStatus } from '../types';
import { CheckCircle, XCircle, AlertTriangle, Hash, ArrowRight, Copy } from 'lucide-react';

export function CustomerDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();

  const { data, isLoading, error } = useQuery({
    queryKey: ['customer', id],
    queryFn: () => http.get<Customer>(`/customers/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (error) return <p className="text-red-600">Failed to load customer.</p>;
  if (!data) return null;

  const timeline = buildTimeline(data);
  const userRoles = user?.roles ?? [];

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between">
        <div>
          <Link to="/customers" className="text-sm text-gray-500 hover:text-gray-700">← Back to customers</Link>
          <h1 className="text-2xl font-bold mt-1">{data.full_name}</h1>
          <p className="text-sm text-gray-500">
            Reference: <span className="font-mono">{data.reference_code ?? <span className="text-gray-400">Pending issuance</span>}</span>
          </p>
        </div>
        <span className={`badge ${statusBadgeClass(data.status)}`}>{data.status_label}</span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left column */}
        <div className="lg:col-span-2 space-y-4">
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

          {/* Role-aware action panels */}
          {userRoles.includes('team_leader') && isEvaluatable(data.status) && (
            <EvaluatePanel customer={data} />
          )}
          {userRoles.includes('record_officer') && data.status === 'pending_record_approval' && (
            <ApprovePanel customer={data} />
          )}
        </div>

        {/* Right column — timeline */}
        <div className="space-y-4">
          <Timeline steps={timeline} />
        </div>
      </div>
    </div>
  );
}

function isEvaluatable(status: CustomerStatus): boolean {
  return ['submitted', 'pending_team_evaluation'].includes(status);
}

// ═════════════════════════════════════════════════════════════════════
//  EVALUATE PANEL (Team Leader)
// ═════════════════════════════════════════════════════════════════════

function EvaluatePanel({ customer }: { customer: Customer }) {
  const queryClient = useQueryClient();
  const [comment, setComment] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState<string | null>(null);

  const act = async (decision: 'approve' | 'reject' | 'correction_required') => {
    setError(null);
    setSubmitting(decision);
    try {
      await http.post(`/customers/${customer.id}/evaluate`, { decision, comment: comment || null });
      await queryClient.invalidateQueries({ queryKey: ['customer', String(customer.id)] });
      await queryClient.invalidateQueries({ queryKey: ['customers'] });
      setComment('');
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? `Failed to ${decision} customer.`);
    } finally {
      setSubmitting(null);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <CheckCircle className="h-4 w-4 text-beha-navy" />
        Team Leader Evaluation
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Review the customer and decide whether to forward to the Record Officer for approval.
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <textarea
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        className="input min-h-[80px] mb-3"
        placeholder="Add a comment (optional for approval, required for rejection)…"
      />
      <div className="flex flex-wrap gap-2">
        <button onClick={() => act('approve')} disabled={submitting !== null} className="btn-primary">
          {submitting === 'approve' ? 'Working…' : 'Approve & Forward'}
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
//  APPROVE PANEL (Record Officer — with duplicate detection)
// ═════════════════════════════════════════════════════════════════════

interface DuplicateMatch {
  existing_customer_id: number;
  existing_reference: string;
  existing_name: string;
  match_field: string;
  match_score: number;
}

function ApprovePanel({ customer }: { customer: Customer }) {
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [duplicates, setDuplicates] = useState<DuplicateMatch[] | null>(null);

  const checkAndApprove = async () => {
    setError(null);
    setDuplicates(null);
    setSubmitting(true);
    try {
      await http.post(`/customers/${customer.id}/approve`);
      await queryClient.invalidateQueries({ queryKey: ['customer', String(customer.id)] });
      await queryClient.invalidateQueries({ queryKey: ['customers'] });
    } catch (err: unknown) {
      const response = (err as { response?: { status?: number; data?: { message?: string; duplicates?: DuplicateMatch[] } } })?.response;
      if (response?.status === 409 && response.data?.duplicates) {
        setDuplicates(response.data.duplicates);
      } else {
        setError(response?.data?.message ?? 'Failed to approve customer.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const resolveDuplicate = async (duplicateId: number, action: 'kept_existing' | 'created_new') => {
    setError(null);
    setSubmitting(true);
    try {
      await http.post(`/customers/${customer.id}/duplicates/${duplicateId}/resolve`, { action });
      setDuplicates(null);
      await queryClient.invalidateQueries({ queryKey: ['customer', String(customer.id)] });
      await queryClient.invalidateQueries({ queryKey: ['customers'] });
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to resolve duplicate.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <Hash className="h-4 w-4 text-beha-navy" />
        Record Officer Approval
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Approve this customer. The system will check for duplicates before issuing a reference code (spec §9).
      </p>

      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}

      {duplicates ? (
        <div className="space-y-3">
          <div className="px-3 py-2 rounded bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-2">
            <AlertTriangle className="h-4 w-4" />
            {duplicates.length} potential duplicate customer(s) found. Please review and resolve.
          </div>
          {duplicates.map((d) => (
            <div key={d.existing_customer_id} className="border border-gray-200 rounded p-3">
              <div className="flex items-start justify-between">
                <div>
                  <p className="font-medium">{d.existing_name}</p>
                  <p className="text-xs text-gray-500 font-mono">{d.existing_reference}</p>
                  <p className="text-xs text-gray-500 mt-1">
                    Match: <span className="font-mono">{d.match_field}</span> · {(d.match_score * 100).toFixed(0)}% confidence
                  </p>
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={() => resolveDuplicate(d.existing_customer_id, 'kept_existing')}
                    disabled={submitting}
                    className="btn-secondary text-xs"
                    title="Reject the new submission and keep the existing customer's reference code"
                  >
                    <Copy className="h-3 w-3 inline mr-1" /> Keep Existing
                  </button>
                  <button
                    onClick={() => resolveDuplicate(d.existing_customer_id, 'created_new')}
                    disabled={submitting}
                    className="btn-primary text-xs"
                    title="Confirm this is a unique customer and issue a new reference code"
                  >
                    Confirm New
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <button onClick={checkAndApprove} disabled={submitting} className="btn-primary">
          {submitting ? 'Checking duplicates…' : 'Approve & Generate Reference Code'}
        </button>
      )}
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

function buildTimeline(customer: Customer): TimelineStep[] {
  const statuses: CustomerStatus[] = [
    'draft',
    'submitted',
    'pending_team_evaluation',
    'approved_by_team',
    'pending_record_approval',
    'registered',
  ];

  const currentIndex = statuses.indexOf(customer.status);
  const isRejected = customer.status === 'rejected';
  const isCorrection = customer.status === 'correction_required';

  const labelMap: Record<string, string> = {
    draft: 'Draft Created',
    submitted: 'Submitted by Team Member',
    pending_team_evaluation: 'Pending Team Leader Evaluation',
    approved_by_team: 'Approved by Team Leader',
    pending_record_approval: 'Pending Record Officer Approval',
    registered: 'Registered — Reference Code Issued',
  };

  return statuses.map((status, i) => {
    let stepStatus: TimelineStep['status'];
    if (isRejected) {
      stepStatus = i < currentIndex ? 'completed' : 'pending';
    } else if (i < currentIndex) {
      stepStatus = 'completed';
    } else if (i === currentIndex) {
      stepStatus = customer.status === 'registered' ? 'completed' : 'current';
    } else {
      stepStatus = 'pending';
    }

    return {
      label: labelMap[status] ?? status,
      status: stepStatus,
      timestamp: i === 0 ? customer.created_at : (i === currentIndex ? customer.updated_at : undefined),
    };
  });
}
