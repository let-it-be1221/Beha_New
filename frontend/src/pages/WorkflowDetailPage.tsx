import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useParams } from 'react-router-dom';
import { http } from '../lib/api';
import { formatDate } from '../lib/utils';
import type { WorkflowInstance } from '../types';
import { useState } from 'react';

export function WorkflowDetailPage() {
  const { id } = useParams<{ id: string }>();
  const queryClient = useQueryClient();
  const [action, setAction] = useState('approve');
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['workflow', id],
    queryFn: () => http.get<WorkflowInstance>(`/workflows/${id}`).then((r) => r.data),
    enabled: Boolean(id),
  });

  const handleAdvance = async () => {
    setSubmitting(true);
    setError(null);
    try {
      await http.post(`/workflows/${id}/advance`, { action, comment });
      await queryClient.invalidateQueries({ queryKey: ['workflow', id] });
      setComment('');
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to advance workflow.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleReject = async () => {
    if (!comment) {
      setError('A reason is required for rejection.');
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      await http.post(`/workflows/${id}/reject`, { reason: comment });
      await queryClient.invalidateQueries({ queryKey: ['workflow', id] });
      setComment('');
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string } } })?.response;
      setError(response?.data?.message ?? 'Failed to reject workflow.');
    } finally {
      setSubmitting(false);
    }
  };

  if (isLoading) return <p className="text-gray-500">Loading…</p>;
  if (!data) return null;

  return (
    <div className="space-y-4">
      <div>
        <Link to="/workflows" className="text-sm text-gray-500 hover:text-gray-700">← Back to workflows</Link>
        <h1 className="text-2xl font-bold mt-1">
          {data.workflow_type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
        </h1>
        <p className="text-sm text-gray-500">
          Subject: <span className="font-mono">{data.subject_type}:{data.subject_id}</span>
        </p>
      </div>

      <div className="card p-5">
        <h2 className="font-semibold mb-3">Action Panel</h2>
        <div className="space-y-3">
          <div>
            <label className="text-sm font-medium text-gray-700 mb-1 block">Action</label>
            <select value={action} onChange={(e) => setAction(e.target.value)} className="input">
              <option value="approve">Approve & advance</option>
              <option value="verify">Verify</option>
              <option value="forward">Forward to next</option>
              <option value="publish">Publish</option>
              <option value="evaluate">Evaluate</option>
              <option value="assign">Assign</option>
              <option value="generate">Generate IDs</option>
              <option value="finalize">Finalize</option>
            </select>
          </div>
          <div>
            <label className="text-sm font-medium text-gray-700 mb-1 block">Comment</label>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              className="input min-h-[80px]"
              placeholder="Add a comment (required for rejection)…"
            />
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex gap-2">
            <button onClick={handleAdvance} disabled={submitting} className="btn-primary">
              {submitting ? 'Working…' : 'Advance'}
            </button>
            <button onClick={handleReject} disabled={submitting} className="btn-danger">
              Reject
            </button>
          </div>
        </div>
      </div>

      {data.actions && data.actions.length > 0 && (
        <div className="card p-5">
          <h2 className="font-semibold mb-3">Timeline</h2>
          <ul className="space-y-3 text-sm">
            {data.actions.slice().reverse().map((a) => (
              <li key={a.id} className="flex justify-between border-b border-gray-100 pb-2">
                <div>
                  <p className="font-medium capitalize">{a.action}</p>
                  <p className="text-xs text-gray-500">
                    {a.from_step} → {a.to_step ?? '(final)'}
                  </p>
                  {a.comment && <p className="text-xs text-gray-600 mt-1">{a.comment}</p>}
                </div>
                <p className="text-xs text-gray-500">{formatDate(a.created_at)}</p>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
