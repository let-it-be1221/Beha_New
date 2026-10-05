import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { http } from '../lib/api';
import { Modal } from '../components/Modal';
import { Network, Pencil, Plus, Trash2, Users } from 'lucide-react';
import type { Branch, Generation, Team } from '../types';
import { cn } from '../lib/utils';

type Tab = 'generations' | 'branches' | 'teams';

export function OrganizationPage() {
  const [tab, setTab] = useState<Tab>('generations');

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Organization</h1>
        <p className="text-sm text-gray-500">
          Manage generations, branches, and teams. Capacity limits are enforced (spec §16).
        </p>
      </div>

      <div className="border-b border-gray-200 flex gap-4">
        <TabButton active={tab === 'generations'} onClick={() => setTab('generations')} icon={Network}>
          Generations
        </TabButton>
        <TabButton active={tab === 'branches'} onClick={() => setTab('branches')} icon={Network}>
          Branches
        </TabButton>
        <TabButton active={tab === 'teams'} onClick={() => setTab('teams')} icon={Users}>
          Teams
        </TabButton>
      </div>

      {tab === 'generations' && <GenerationsTab />}
      {tab === 'branches' && <BranchesTab />}
      {tab === 'teams' && <TeamsTab />}
    </div>
  );
}

function TabButton({
  active,
  onClick,
  icon: Icon,
  children,
}: {
  active: boolean;
  onClick: () => void;
  icon: React.ComponentType<{ className?: string }>;
  children: React.ReactNode;
}) {
  return (
    <button
      onClick={onClick}
      className={cn(
        'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 -mb-px transition',
        active ? 'border-beha-navy text-beha-navy' : 'border-transparent text-gray-500 hover:text-gray-700',
      )}
    >
      <Icon className="h-4 w-4" />
      {children}
    </button>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  GENERATIONS TAB
// ═════════════════════════════════════════════════════════════════════

interface GenerationRow extends Generation {
  branches_count?: number;
}

function GenerationsTab() {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<GenerationRow | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['generations'],
    queryFn: async () => {
      const res = await http.get<{ data: GenerationRow[] }>('generations');
      return res.data.data ?? [];
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => http.delete(`/generations/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['generations'] }),
  });

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="font-semibold">All Generations</h2>
        <button
          onClick={() => { setEditing(null); setModalOpen(true); }}
          className="btn-primary"
        >
          <Plus className="h-4 w-4 mr-1 inline" />
          New Generation
        </button>
      </div>

      {isLoading && <p className="text-gray-500">Loading…</p>}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {data?.map((g) => (
          <div key={g.id} className="card p-5">
            <div className="flex items-start justify-between">
              <div>
                <h3 className="font-semibold">{g.name}</h3>
                <p className="text-xs text-gray-500 font-mono">#{g.generation_number}</p>
              </div>
              <div className="flex gap-1">
                <button
                  onClick={() => { setEditing(g); setModalOpen(true); }}
                  className="p-1 text-gray-400 hover:text-beha-navy"
                  aria-label="Edit"
                >
                  <Pencil className="h-4 w-4" />
                </button>
                <button
                  onClick={() => {
                    if (confirm(`Delete generation "${g.name}"? This cannot be undone.`)) {
                      deleteMutation.mutate(g.id);
                    }
                  }}
                  className="p-1 text-gray-400 hover:text-red-600"
                  aria-label="Delete"
                >
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            </div>
            <dl className="mt-3 text-sm space-y-1">
              <div className="flex justify-between">
                <dt className="text-gray-500">Leader</dt>
                <dd>{g.leader?.username ?? '—'}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Branches</dt>
                <dd>{g.branches?.length ?? 0}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Status</dt>
                <dd>{g.is_active ? 'Active' : 'Inactive'}</dd>
              </div>
            </dl>
          </div>
        ))}
        {data?.length === 0 && (
          <div className="card p-8 text-center text-gray-500 col-span-full">
            No generations yet. Create the first one.
          </div>
        )}
      </div>

      <GenerationModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        editing={editing}
      />
    </div>
  );
}

function GenerationModal({
  open,
  onClose,
  editing,
}: {
  open: boolean;
  onClose: () => void;
  editing: GenerationRow | null;
}) {
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: '',
    generation_number: '',
    leader_user_id: '',
    description: '',
    is_active: true,
  });

  // Sync form when modal opens
  useState(() => {
    if (editing) {
      setForm({
        name: editing.name,
        generation_number: String(editing.generation_number),
        leader_user_id: editing.leader_user_id ? String(editing.leader_user_id) : '',
        description: editing.description ?? '',
        is_active: editing.is_active,
      });
    }
  });

  const mutation = useMutation({
    mutationFn: async () => {
      const payload = {
        name: form.name,
        generation_number: Number(form.generation_number),
        leader_user_id: form.leader_user_id ? Number(form.leader_user_id) : null,
        description: form.description || null,
        is_active: form.is_active,
      };
      if (editing) {
        await http.put(`/generations/${editing.id}`, payload);
      } else {
        await http.post('/generations', payload);
      }
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['generations'] });
      onClose();
      setForm({ name: '', generation_number: '', leader_user_id: '', description: '', is_active: true });
      setError(null);
    },
    onError: (err: unknown) => {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      setError(response?.data?.message ?? 'Failed to save generation.');
    },
  });

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={editing ? 'Edit Generation' : 'New Generation'}
      description="A generation holds up to 10 branches (configurable)."
      footer={
        <>
          <button onClick={onClose} className="btn-secondary">Cancel</button>
          <button onClick={() => mutation.mutate()} disabled={mutation.isPending} className="btn-primary">
            {mutation.isPending ? 'Saving…' : 'Save'}
          </button>
        </>
      }
    >
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <div className="space-y-3">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="input" placeholder="Generation 1" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Generation Number *</label>
          <input type="number" min="1" value={form.generation_number} onChange={(e) => setForm({ ...form, generation_number: e.target.value })} className="input" placeholder="1" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Leader User ID (optional)</label>
          <input type="number" value={form.leader_user_id} onChange={(e) => setForm({ ...form, leader_user_id: e.target.value })} className="input" placeholder="e.g. 5" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="input min-h-[60px]" />
        </div>
        <label className="flex items-center text-sm">
          <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="mr-2" />
          Active
        </label>
      </div>
    </Modal>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  BRANCHES TAB
// ═════════════════════════════════════════════════════════════════════

interface BranchRow extends Branch {
  generation?: { name: string; generation_number: number };
}

function BranchesTab() {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<BranchRow | null>(null);

  const { data: generations } = useQuery({
    queryKey: ['generations'],
    queryFn: async () => {
      const res = await http.get<{ data: Generation[] }>('generations');
      return res.data.data ?? [];
    },
  });

  const { data, isLoading } = useQuery({
    queryKey: ['branches'],
    queryFn: async () => {
      const res = await http.get<{ data: BranchRow[] }>('branches');
      return res.data.data ?? [];
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => http.delete(`/branches/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['branches'] }),
  });

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="font-semibold">All Branches</h2>
        <button
          onClick={() => { setEditing(null); setModalOpen(true); }}
          className="btn-primary"
          disabled={!generations?.length}
        >
          <Plus className="h-4 w-4 mr-1 inline" />
          New Branch
        </button>
      </div>

      {!generations?.length && (
        <div className="card p-4 bg-amber-50 border-amber-200 text-amber-800 text-sm">
          Create at least one generation before adding branches.
        </div>
      )}

      {isLoading && <p className="text-gray-500">Loading…</p>}

      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
            <tr>
              <th className="px-4 py-3">Name</th>
              <th className="px-4 py-3">#</th>
              <th className="px-4 py-3">Generation</th>
              <th className="px-4 py-3">Leader</th>
              <th className="px-4 py-3">Teams</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {data?.map((b) => (
              <tr key={b.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium">{b.name}</td>
                <td className="px-4 py-3 text-gray-500">#{b.branch_number}</td>
                <td className="px-4 py-3">{b.generation?.name ?? '—'}</td>
                <td className="px-4 py-3">{b.leader?.username ?? '—'}</td>
                <td className="px-4 py-3">{b.teams?.length ?? 0}</td>
                <td className="px-4 py-3 text-right">
                  <button onClick={() => { setEditing(b); setModalOpen(true); }} className="p-1 text-gray-400 hover:text-beha-navy mr-1">
                    <Pencil className="h-4 w-4" />
                  </button>
                  <button
                    onClick={() => { if (confirm(`Delete branch "${b.name}"?`)) deleteMutation.mutate(b.id); }}
                    className="p-1 text-gray-400 hover:text-red-600"
                  >
                    <Trash2 className="h-4 w-4" />
                  </button>
                </td>
              </tr>
            ))}
            {data?.length === 0 && (
              <tr><td colSpan={6} className="px-4 py-8 text-center text-gray-500">No branches yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <BranchModal open={modalOpen} onClose={() => setModalOpen(false)} editing={editing} generations={generations ?? []} />
    </div>
  );
}

function BranchModal({
  open,
  onClose,
  editing,
  generations,
}: {
  open: boolean;
  onClose: () => void;
  editing: BranchRow | null;
  generations: Generation[];
}) {
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    generation_id: '',
    name: '',
    branch_number: '',
    leader_user_id: '',
    description: '',
    is_active: true,
  });

  useState(() => {
    if (editing) {
      setForm({
        generation_id: String(editing.generation_id),
        name: editing.name,
        branch_number: String(editing.branch_number),
        leader_user_id: editing.leader_user_id ? String(editing.leader_user_id) : '',
        description: editing.description ?? '',
        is_active: editing.is_active,
      });
    }
  });

  const mutation = useMutation({
    mutationFn: async () => {
      const payload = {
        generation_id: Number(form.generation_id),
        name: form.name,
        branch_number: Number(form.branch_number),
        leader_user_id: form.leader_user_id ? Number(form.leader_user_id) : null,
        description: form.description || null,
        is_active: form.is_active,
      };
      if (editing) {
        await http.put(`/branches/${editing.id}`, payload);
      } else {
        await http.post('/branches', payload);
      }
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['branches'] });
      onClose();
      setError(null);
    },
    onError: (err: unknown) => {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      setError(response?.data?.message ?? 'Failed to save branch.');
    },
  });

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={editing ? 'Edit Branch' : 'New Branch'}
      description="A branch holds up to 10 teams (configurable)."
      footer={
        <>
          <button onClick={onClose} className="btn-secondary">Cancel</button>
          <button onClick={() => mutation.mutate()} disabled={mutation.isPending} className="btn-primary">
            {mutation.isPending ? 'Saving…' : 'Save'}
          </button>
        </>
      }
    >
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <div className="space-y-3">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Generation *</label>
          <select value={form.generation_id} onChange={(e) => setForm({ ...form, generation_id: e.target.value })} className="input">
            <option value="">Select generation…</option>
            {generations.map((g) => (
              <option key={g.id} value={g.id}>{g.name} (#{g.generation_number})</option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="input" placeholder="Branch 1" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Branch Number *</label>
          <input type="number" min="1" value={form.branch_number} onChange={(e) => setForm({ ...form, branch_number: e.target.value })} className="input" placeholder="1" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Leader User ID (optional)</label>
          <input type="number" value={form.leader_user_id} onChange={(e) => setForm({ ...form, leader_user_id: e.target.value })} className="input" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="input min-h-[60px]" />
        </div>
        <label className="flex items-center text-sm">
          <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="mr-2" />
          Active
        </label>
      </div>
    </Modal>
  );
}

// ═════════════════════════════════════════════════════════════════════
//  TEAMS TAB
// ═════════════════════════════════════════════════════════════════════

interface TeamRow extends Team {
  branch?: { name: string; branch_number: number; generation?: { name: string } };
}

function TeamsTab() {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<TeamRow | null>(null);

  const { data: branches } = useQuery({
    queryKey: ['branches'],
    queryFn: async () => {
      const res = await http.get<{ data: Branch[] }>('branches');
      return res.data.data ?? [];
    },
  });

  const { data, isLoading } = useQuery({
    queryKey: ['teams'],
    queryFn: async () => {
      const res = await http.get<{ data: TeamRow[] }>('teams');
      return res.data.data ?? [];
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => http.delete(`/teams/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['teams'] }),
  });

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="font-semibold">All Teams</h2>
        <button
          onClick={() => { setEditing(null); setModalOpen(true); }}
          className="btn-primary"
          disabled={!branches?.length}
        >
          <Plus className="h-4 w-4 mr-1 inline" />
          New Team
        </button>
      </div>

      {!branches?.length && (
        <div className="card p-4 bg-amber-50 border-amber-200 text-amber-800 text-sm">
          Create at least one branch before adding teams.
        </div>
      )}

      {isLoading && <p className="text-gray-500">Loading…</p>}

      <div className="card overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
            <tr>
              <th className="px-4 py-3">Name</th>
              <th className="px-4 py-3">#</th>
              <th className="px-4 py-3">Branch</th>
              <th className="px-4 py-3">Generation</th>
              <th className="px-4 py-3">Leader</th>
              <th className="px-4 py-3">Members</th>
              <th className="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {data?.map((t) => (
              <tr key={t.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium">{t.name}</td>
                <td className="px-4 py-3 text-gray-500">#{t.team_number}</td>
                <td className="px-4 py-3">{t.branch?.name ?? '—'}</td>
                <td className="px-4 py-3">{t.branch?.generation?.name ?? '—'}</td>
                <td className="px-4 py-3">{t.leader?.username ?? '—'}</td>
                <td className="px-4 py-3">{t.members?.length ?? 0}</td>
                <td className="px-4 py-3 text-right">
                  <button onClick={() => { setEditing(t); setModalOpen(true); }} className="p-1 text-gray-400 hover:text-beha-navy mr-1">
                    <Pencil className="h-4 w-4" />
                  </button>
                  <button
                    onClick={() => { if (confirm(`Delete team "${t.name}"?`)) deleteMutation.mutate(t.id); }}
                    className="p-1 text-gray-400 hover:text-red-600"
                  >
                    <Trash2 className="h-4 w-4" />
                  </button>
                </td>
              </tr>
            ))}
            {data?.length === 0 && (
              <tr><td colSpan={7} className="px-4 py-8 text-center text-gray-500">No teams yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <TeamModal open={modalOpen} onClose={() => setModalOpen(false)} editing={editing} branches={branches ?? []} />
    </div>
  );
}

function TeamModal({
  open,
  onClose,
  editing,
  branches,
}: {
  open: boolean;
  onClose: () => void;
  editing: TeamRow | null;
  branches: Branch[];
}) {
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    branch_id: '',
    name: '',
    team_number: '',
    leader_user_id: '',
    description: '',
    is_active: true,
  });

  useState(() => {
    if (editing) {
      setForm({
        branch_id: String(editing.branch_id),
        name: editing.name,
        team_number: String(editing.team_number),
        leader_user_id: editing.leader_user_id ? String(editing.leader_user_id) : '',
        description: editing.description ?? '',
        is_active: editing.is_active,
      });
    }
  });

  const mutation = useMutation({
    mutationFn: async () => {
      const payload = {
        branch_id: Number(form.branch_id),
        name: form.name,
        team_number: Number(form.team_number),
        leader_user_id: form.leader_user_id ? Number(form.leader_user_id) : null,
        description: form.description || null,
        is_active: form.is_active,
      };
      if (editing) {
        await http.put(`/teams/${editing.id}`, payload);
      } else {
        await http.post('/teams', payload);
      }
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['teams'] });
      onClose();
      setError(null);
    },
    onError: (err: unknown) => {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      setError(response?.data?.message ?? 'Failed to save team.');
    },
  });

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={editing ? 'Edit Team' : 'New Team'}
      description="A team holds up to 10 members (configurable)."
      footer={
        <>
          <button onClick={onClose} className="btn-secondary">Cancel</button>
          <button onClick={() => mutation.mutate()} disabled={mutation.isPending} className="btn-primary">
            {mutation.isPending ? 'Saving…' : 'Save'}
          </button>
        </>
      }
    >
      {error && <div className="mb-3 px-3 py-2 rounded bg-red-50 border border-red-200 text-red-800 text-sm">{error}</div>}
      <div className="space-y-3">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Branch *</label>
          <select value={form.branch_id} onChange={(e) => setForm({ ...form, branch_id: e.target.value })} className="input">
            <option value="">Select branch…</option>
            {branches.map((b) => (
              <option key={b.id} value={b.id}>{b.name} (#{b.branch_number})</option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
          <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="input" placeholder="Team 1" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Team Number *</label>
          <input type="number" min="1" value={form.team_number} onChange={(e) => setForm({ ...form, team_number: e.target.value })} className="input" placeholder="1" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Leader User ID (optional)</label>
          <input type="number" value={form.leader_user_id} onChange={(e) => setForm({ ...form, leader_user_id: e.target.value })} className="input" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="input min-h-[60px]" />
        </div>
        <label className="flex items-center text-sm">
          <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="mr-2" />
          Active
        </label>
      </div>
    </Modal>
  );
}
