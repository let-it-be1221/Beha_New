import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { useAuth } from '../lib/auth';
import { Modal } from '../components/Modal';
import { Timeline, type TimelineStep } from '../components/Timeline';
import { formatDate, statusBadgeClass } from '../lib/utils';
import type { Applicant, Generation, Branch, Team, ApplicantStatus } from '../types';
import { CheckCircle, XCircle, AlertTriangle, IdCard, UserPlus, ArrowRight } from 'lucide-react';

export function ApplicantDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const queryClient = useQueryClient();

  const { data, isLoading, error } = useQuery({
    queryKey: ['applicant', id],
    queryFn: () => http.get<Applicant>(`/applicants/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  const invalidate = async () => {
    await queryClient.invalidateQueries({ queryKey: ['applicant', id] });
    await queryClient.invalidateQueries({ queryKey: ['applicants'] });
  };

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (error) return <p className="text-red-600">Failed to load applicant.</p>;
  if (!data) return null;

  // Compute timeline steps based on current status
  const timeline = buildTimeline(data);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <Link to="/applicants" className="text-sm text-gray-500 hover:text-gray-700">← Back to applicants</Link>
          <h1 className="text-2xl font-bold mt-1">{data.full_name}</h1>
          <p className="text-sm text-gray-500 font-mono">{data.application_code}</p>
        </div>
        <span className={`badge ${statusBadgeClass(data.status)}`}>{data.status_label}</span>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left column — info + actions */}
        <div className="lg:col-span-2 space-y-4">
          {/* Contact info */}
          <div className="card p-5">
            <h2 className="font-semibold mb-3">Contact Information</h2>
            <dl className="text-sm space-y-2">
              <Row label="Email" value={data.email} />
              <Row label="Phone" value={data.phone} />
              <Row label="Applied" value={formatDate(data.created_at)} />
              {data.approved_at && <Row label="Approved" value={formatDate(data.approved_at)} />}
              {data.rejected_at && <Row label="Rejected" value={formatDate(data.rejected_at)} />}
              {data.rejection_reason && <Row label="Rejection Reason" value={data.rejection_reason} />}
            </dl>
          </div>

          {/* Assignment */}
          {data.assigned_generation && (
            <div className="card p-5">
              <h2 className="font-semibold mb-3">Organizational Assignment</h2>
              <dl className="text-sm space-y-2">
                <Row label="Generation" value={data.assigned_generation?.name ?? '—'} />
                <Row label="Branch" value={data.assigned_branch?.name ?? '—'} />
                <Row label="Team" value={data.assigned_team?.name ?? '—'} />
              </dl>
            </div>
          )}

          {/* Role-aware action panels */}
          <ActionPanel applicant={data} onAction={invalidate} canAct={Boolean(user)} userRoles={user?.roles ?? []} />
        </div>

        {/* Right column — timeline + IDs */}
        <div className="space-y-4">
          <Timeline steps={timeline} />

          {data.official_id && (
            <div className="card p-5">
              <h2 className="font-semibold mb-3 flex items-center gap-2">
                <IdCard className="h-4 w-4" />
                Generated IDs
              </h2>
              <dl className="text-sm space-y-2">
                <Row label="Official ID" value={data.official_id} />
                <Row label="Confidential ID" value="(encrypted — viewable by authorized roles only)" />
              </dl>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  ROLE-AWARE ACTION PANEL
// ═════════════════════════════════════════════════════════════════════

function ActionPanel({
  applicant,
  onAction,
  canAct,
  userRoles,
}: {
  applicant: Applicant;
  onAction: () => Promise<void>;
  canAct: boolean;
  userRoles: string[];
}) {
  if (!canAct) return null;

  // Team Leader screen panel
  if (userRoles.includes('team_leader') && isScreenable(applicant.status)) {
    return <ScreenPanel applicant={applicant} onAction={onAction} />;
  }

  // Branch Leader assignment panel
  if (userRoles.includes('branch_leader') && applicant.status === 'branch_assignment') {
    return <AssignPanel applicant={applicant} onAction={onAction} />;
  }

  // Record Officer ID generation panel
  if (userRoles.includes('record_officer') && applicant.status === 'record_verification') {
    return <GenerateIdsPanel applicant={applicant} onAction={onAction} />;
  }

  // System Administrator account creation panel
  if (userRoles.includes('system_administrator') && applicant.status === 'account_creation') {
    return <CreateAccountPanel applicant={applicant} onAction={onAction} />;
  }

  // No action available for this user at this stage
  return null;
}

function isScreenable(status: ApplicantStatus): boolean {
  return ['submitted', 'under_review', 'team_leader_screening'].includes(status);
}

// ─── Team Leader panel ────────────────────────────────────────────────

function ScreenPanel({ applicant, onAction }: { applicant: Applicant; onAction: () => Promise<void> }) {
  const [comment, setComment] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState<string | null>(null);

  const act = async (decision: 'approve' | 'reject' | 'correction_required') => {
    setError(null);
    setSubmitting(decision);
    try {
      await http.post(`/applicants/${applicant.id}/screen`, { decision, comment: comment || null });
      await onAction();
      setComment('');
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      setError(response?.data?.message ?? `Failed to ${decision} applicant.`);
    } finally {
      setSubmitting(null);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <CheckCircle className="h-4 w-4 text-beha-navy" />
        Team Leader Screening
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Review the applicant and decide whether to forward to a Branch Leader for assignment.
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <textarea
        value={comment}
        onChange={(e) => setComment(e.target.value)}
        className="input min-h-[80px] mb-3"
        placeholder="Add a comment (optional for approval, required for rejection / correction)…"
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

// ─── Branch Leader assignment panel ───────────────────────────────────

function AssignPanel({ applicant, onAction }: { applicant: Applicant; onAction: () => Promise<void> }) {
  const [generationId, setGenerationId] = useState('');
  const [branchId, setBranchId] = useState('');
  const [teamId, setTeamId] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const { data: generations } = useQuery({
    queryKey: ['generations'],
    queryFn: async () => {
      const res = await http.get<{ data: Generation[] }>('generations');
      return res.data.data ?? [];
    },
  });

  const { data: branches } = useQuery({
    queryKey: ['branches'],
    queryFn: async () => {
      const res = await http.get<{ data: Branch[] }>('branches');
      return res.data.data ?? [];
    },
  });

  const { data: teams } = useQuery({
    queryKey: ['teams'],
    queryFn: async () => {
      const res = await http.get<{ data: Team[] }>('teams');
      return res.data.data ?? [];
    },
  });

  const filteredBranches = branches?.filter((b) => !generationId || b.generation_id === Number(generationId)) ?? [];
  const filteredTeams = teams?.filter((t) => !branchId || t.branch_id === Number(branchId)) ?? [];

  const submit = async () => {
    setError(null);
    if (!generationId || !branchId || !teamId) {
      setError('Please select generation, branch, and team.');
      return;
    }
    setSubmitting(true);
    try {
      await http.post(`/applicants/${applicant.id}/assign`, {
        generation_id: Number(generationId),
        branch_id: Number(branchId),
        team_id: Number(teamId),
      });
      await onAction();
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      const firstError = response?.data?.errors ? Object.values(response.data.errors)[0]?.[0] : undefined;
      setError(firstError ?? response?.data?.message ?? 'Failed to assign applicant.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <UserPlus className="h-4 w-4 text-beha-navy" />
        Branch Assignment
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Assign this applicant to a generation → branch → team. Capacity is enforced (spec §16).
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <div className="space-y-3">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Generation *</label>
          <select value={generationId} onChange={(e) => { setGenerationId(e.target.value); setBranchId(''); setTeamId(''); }} className="input">
            <option value="">Select generation…</option>
            {generations?.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
          <select value={branchId} onChange={(e) => { setBranchId(e.target.value); setTeamId(''); }} className="input" disabled={!generationId}>
            <option value="">Select branch…</option>
            {filteredBranches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Team *</label>
          <select value={teamId} onChange={(e) => setTeamId(e.target.value)} className="input" disabled={!branchId}>
            <option value="">Select team…</option>
            {filteredTeams.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
          </select>
        </div>
        <button onClick={submit} disabled={submitting} className="btn-primary">
          {submitting ? 'Assigning…' : 'Assign & Forward'}
          <ArrowRight className="h-4 w-4 ml-1 inline" />
        </button>
      </div>
    </div>
  );
}

// ─── Record Officer ID generation panel ───────────────────────────────

function GenerateIdsPanel({ applicant, onAction }: { applicant: Applicant; onAction: () => Promise<void> }) {
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [result, setResult] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    setSubmitting(true);
    try {
      const res = await http.post<{ official_id: string }>(`/applicants/${applicant.id}/generate-ids`);
      setResult(res.data.official_id);
      await onAction();
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to generate IDs.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <IdCard className="h-4 w-4 text-beha-navy" />
        Identity Generation
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Generate the Official ID (e.g. <code>BH000123</code>) and the encrypted Confidential ID for this applicant.
        Once generated, a System Administrator will create the user account.
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      {result && (
        <div className="mb-3 px-3 py-2 rounded bg-green-50 border border-green-200 text-green-800 text-sm">
          ✓ Generated Official ID: <span className="font-mono font-bold">{result}</span>
        </div>
      )}
      <button onClick={submit} disabled={submitting || Boolean(result)} className="btn-primary">
        {submitting ? 'Generating…' : result ? 'IDs Generated' : 'Generate Official + Confidential IDs'}
      </button>
    </div>
  );
}

// ─── System Administrator account creation panel ──────────────────────

function CreateAccountPanel({ applicant, onAction }: { applicant: Applicant; onAction: () => Promise<void> }) {
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState(applicant.email);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [tempPassword, setTempPassword] = useState<string | null>(null);

  const submit = async () => {
    setError(null);
    if (!username || !email) {
      setError('Username and email are required.');
      return;
    }
    setSubmitting(true);
    try {
      const res = await http.post<{ temp_password: string }>(`/applicants/${applicant.id}/create-account`, {
        username,
        email,
      });
      setTempPassword(res.data.temp_password);
      await onAction();
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      const firstError = response?.data?.errors ? Object.values(response.data.errors)[0]?.[0] : undefined;
      setError(firstError ?? response?.data?.message ?? 'Failed to create account.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="card p-5">
      <h2 className="font-semibold mb-3 flex items-center gap-2">
        <UserPlus className="h-4 w-4 text-beha-navy" />
        Account Creation
      </h2>
      <p className="text-sm text-gray-500 mb-3">
        Create the system account. The user will be assigned the <code>team_member</code> role at Level 1.
        A cryptographically secure temporary password will be generated (spec §19).
      </p>
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      {tempPassword && (
        <div className="mb-3 px-3 py-2 rounded bg-green-50 border border-green-200 text-green-800 text-sm">
          ✓ Account created. Temporary password (shown once):
          <pre className="mt-1 p-2 bg-white border border-green-300 rounded font-mono">{tempPassword}</pre>
          <p className="text-xs mt-2">Transmit this to the user via a secure channel (e.g. email / SMS).</p>
        </div>
      )}
      <div className="space-y-3">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Username *</label>
          <input value={username} onChange={(e) => setUsername(e.target.value)} className="input" placeholder="johndoe" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} className="input" />
        </div>
        <button onClick={submit} disabled={submitting || Boolean(tempPassword)} className="btn-primary">
          {submitting ? 'Creating account…' : tempPassword ? 'Account Created ✓' : 'Create Account + Generate Temp Password'}
        </button>
      </div>
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

function buildTimeline(applicant: Applicant): TimelineStep[] {
  const statuses: ApplicantStatus[] = [
    'submitted',
    'team_leader_screening',
    'branch_assignment',
    'record_verification',
    'account_creation',
    'approved',
  ];

  const currentIndex = statuses.indexOf(applicant.status);
  const isRejected = applicant.status === 'rejected';

  return statuses.map((status, i) => {
    const labelMap: Record<string, string> = {
      submitted: 'Application Submitted',
      team_leader_screening: 'Team Leader Screening',
      branch_assignment: 'Branch Assignment',
      record_verification: 'Record Officer ID Generation',
      account_creation: 'System Admin Account Creation',
      approved: 'Approved — Account Active',
    };

    let stepStatus: TimelineStep['status'];
    if (isRejected) {
      stepStatus = i < currentIndex ? 'completed' : i === currentIndex - 1 ? 'rejected' : 'pending';
    } else if (i < currentIndex) {
      stepStatus = 'completed';
    } else if (i === currentIndex) {
      stepStatus = applicant.status === 'approved' ? 'completed' : 'current';
    } else {
      stepStatus = 'pending';
    }

    return {
      label: labelMap[status] ?? status,
      status: stepStatus,
      timestamp: i === 0 ? applicant.created_at : (i === currentIndex && applicant.status === 'rejected' ? applicant.rejected_at : undefined),
    };
  });
}
