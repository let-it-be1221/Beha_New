import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { http } from '../lib/api';
import type { Property } from '../types';

export function PropertyCreatePage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [error, setError] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: '',
    property_type: '',
    country: '',
    region: '',
    city: '',
    address: '',
    size_sqm: '',
    number_of_units: '1',
    bedrooms: '',
    bathrooms: '',
    floor: '',
    developer: '',
    ownership_type: '',
    price: '',
    description: '',
  });

  const mutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) =>
      http.post<Property>('/properties', payload).then((r) => r.data),
    onSuccess: async (data) => {
      await queryClient.invalidateQueries({ queryKey: ['properties'] });
      navigate(`/properties/${data.id}`);
    },
    onError: (err: unknown) => {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      const firstError = response?.data?.errors
        ? Object.values(response.data.errors)[0]?.[0]
        : undefined;
      setError(firstError ?? response?.data?.message ?? 'Failed to create property.');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    mutation.mutate({
      name: form.name,
      property_type: form.property_type,
      country: form.country || null,
      region: form.region || null,
      city: form.city || null,
      address: form.address || null,
      size_sqm: form.size_sqm ? Number(form.size_sqm) : null,
      number_of_units: Number(form.number_of_units),
      bedrooms: form.bedrooms ? Number(form.bedrooms) : null,
      bathrooms: form.bathrooms ? Number(form.bathrooms) : null,
      floor: form.floor ? Number(form.floor) : null,
      developer: form.developer || null,
      ownership_type: form.ownership_type || null,
      price: Number(form.price),
      description: form.description || null,
    });
  };

  return (
    <div className="max-w-3xl mx-auto space-y-4">
      <h1 className="text-2xl font-bold">Register New Property</h1>
      <p className="text-sm text-gray-500">
        Property will be created as a draft and routed to the Executive Officer for verification (spec §10).
      </p>

      {error && (
        <div className="px-4 py-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="card p-6 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field label="Property Name" required>
            <input
              type="text"
              required
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Property Type" required>
            <select
              required
              value={form.property_type}
              onChange={(e) => setForm({ ...form, property_type: e.target.value })}
              className="input"
            >
              <option value="">Select type…</option>
              <option value="apartment">Apartment</option>
              <option value="house">House</option>
              <option value="villa">Villa</option>
              <option value="condo">Condo</option>
              <option value="townhouse">Townhouse</option>
              <option value="land">Land</option>
              <option value="commercial">Commercial</option>
            </select>
          </Field>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Field label="Country">
            <input
              type="text"
              value={form.country}
              onChange={(e) => setForm({ ...form, country: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Region / State">
            <input
              type="text"
              value={form.region}
              onChange={(e) => setForm({ ...form, region: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="City">
            <input
              type="text"
              value={form.city}
              onChange={(e) => setForm({ ...form, city: e.target.value })}
              className="input"
            />
          </Field>
        </div>

        <Field label="Address">
          <input
            type="text"
            value={form.address}
            onChange={(e) => setForm({ ...form, address: e.target.value })}
            className="input"
          />
        </Field>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Field label="Size (sqm)">
            <input
              type="number"
              step="0.01"
              value={form.size_sqm}
              onChange={(e) => setForm({ ...form, size_sqm: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Units">
            <input
              type="number"
              min="1"
              value={form.number_of_units}
              onChange={(e) => setForm({ ...form, number_of_units: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Bedrooms">
            <input
              type="number"
              min="0"
              value={form.bedrooms}
              onChange={(e) => setForm({ ...form, bedrooms: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Bathrooms">
            <input
              type="number"
              min="0"
              value={form.bathrooms}
              onChange={(e) => setForm({ ...form, bathrooms: e.target.value })}
              className="input"
            />
          </Field>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Field label="Floor">
            <input
              type="number"
              min="0"
              value={form.floor}
              onChange={(e) => setForm({ ...form, floor: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Developer">
            <input
              type="text"
              value={form.developer}
              onChange={(e) => setForm({ ...form, developer: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Ownership">
            <select
              value={form.ownership_type}
              onChange={(e) => setForm({ ...form, ownership_type: e.target.value })}
              className="input"
            >
              <option value="">Select…</option>
              <option value="freehold">Freehold</option>
              <option value="leasehold">Leasehold</option>
              <option value="cooperative">Cooperative</option>
              <option value="condominium">Condominium</option>
            </select>
          </Field>
        </div>

        <Field label="Price (ETB)" required>
          <input
            type="number"
            step="0.01"
            min="0"
            required
            value={form.price}
            onChange={(e) => setForm({ ...form, price: e.target.value })}
            className="input"
          />
        </Field>

        <Field label="Description">
          <textarea
            value={form.description}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
            className="input min-h-[100px]"
          />
        </Field>

        <div className="flex justify-end gap-2">
          <button type="button" onClick={() => navigate('/properties')} className="btn-secondary">
            Cancel
          </button>
          <button type="submit" disabled={mutation.isPending} className="btn-primary">
            {mutation.isPending ? 'Creating…' : 'Create Property'}
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
