import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { http } from '../lib/api';
import type { Customer } from '../types';

export function CustomerCreatePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    full_name: '',
    email: '',
    phone: '',
    national_id: '',
    date_of_birth: '',
    customer_source: '',
    notes: '',
  });

  const mutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) =>
      http.post<Customer>('/customers', payload).then((r) => r.data),
    onSuccess: async (data) => {
      await queryClient.invalidateQueries({ queryKey: ['customers'] });
      navigate(`/customers/${data.id}`);
    },
    onError: (err: unknown) => {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      const firstError = response?.data?.errors
        ? Object.values(response.data.errors)[0]?.[0]
        : undefined;
      setError(firstError ?? response?.data?.message ?? 'Failed to create customer.');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    mutation.mutate({
      full_name: form.full_name,
      email: form.email || null,
      phone: form.phone,
      national_id: form.national_id || null,
      date_of_birth: form.date_of_birth || null,
      customer_source: form.customer_source || null,
      notes: form.notes || null,
    });
  };

  return (
    <div className="max-w-2xl mx-auto space-y-4">
      <h1 className="text-2xl font-bold">Register New Customer</h1>
      <p className="text-sm text-gray-500">
        Customer will be created as a draft and routed to your Team Leader for evaluation (spec §8).
      </p>

      {error && (
        <div className="px-4 py-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="card p-6 space-y-4">
        <Field label="Full Name" required>
          <input
            type="text"
            required
            value={form.full_name}
            onChange={(e) => setForm({ ...form, full_name: e.target.value })}
            className="input"
          />
        </Field>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field label="Phone" required>
            <input
              type="tel"
              required
              value={form.phone}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Email">
            <input
              type="email"
              value={form.email}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
              className="input"
            />
          </Field>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field label="National ID">
            <input
              type="text"
              value={form.national_id}
              onChange={(e) => setForm({ ...form, national_id: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Date of Birth">
            <input
              type="date"
              value={form.date_of_birth}
              onChange={(e) => setForm({ ...form, date_of_birth: e.target.value })}
              className="input"
            />
          </Field>
        </div>

        <Field label="Customer Source">
          <input
            type="text"
            placeholder="e.g. referral, walk-in, social media"
            value={form.customer_source}
            onChange={(e) => setForm({ ...form, customer_source: e.target.value })}
            className="input"
          />
        </Field>

        <Field label="Notes">
          <textarea
            value={form.notes}
            onChange={(e) => setForm({ ...form, notes: e.target.value })}
            className="input min-h-[80px]"
          />
        </Field>

        <div className="flex justify-end gap-2">
          <button
            type="button"
            onClick={() => navigate('/customers')}
            className="btn-secondary"
          >
            Cancel
          </button>
          <button type="submit" disabled={mutation.isPending} className="btn-primary">
            {mutation.isPending ? 'Creating…' : 'Create Customer'}
          </button>
        </div>
      </form>
    </div>
  );
}

function Field({
  label,
  required,
  children,
}: {
  label: string;
  required?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </label>
      {children}
    </div>
  );
}
