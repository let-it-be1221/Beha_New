import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** Merge Tailwind class names safely. */
export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}

/** Format a price as currency (defaults to ETB for Ethiopian org). */
export function formatPrice(value: number | null | undefined): string {
  if (value === null || value === undefined) return '—';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'ETB',
    maximumFractionDigits: 0,
  }).format(value);
}

/** Format an ISO date as a human-readable string. */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/** Status → badge class */
export function statusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    draft: 'badge-gray',
    submitted: 'badge-amber',
    pending: 'badge-amber',
    registered: 'badge-green',
    approved: 'badge-green',
    verified: 'badge-blue',
    published: 'badge-green',
    rejected: 'badge-red',
    correction_required: 'badge-orange',
  };
  return map[status] ?? 'badge-gray';
}
