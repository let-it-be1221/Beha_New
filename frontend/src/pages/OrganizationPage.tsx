import { useQuery } from '@tanstack/react-query';
import { http } from '../lib/api';
import type { Generation } from '../types';

export function OrganizationPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['organization-tree'],
    queryFn: () =>
      http.get<{ generations: Generation[] }>('/organization/tree').then((r) => r.data),
  });

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Organization Hierarchy</h1>
      <p className="text-sm text-gray-500">
        Spec §30 — interactive tree of Generations → Branches → Teams → Members.
      </p>

      {isLoading && <p className="text-gray-500">Loading…</p>}
      {error && <p className="text-red-600">Failed to load organization.</p>}

      {data && (
        <div className="card p-5">
          {data.generations.length === 0 && (
            <p className="text-sm text-gray-500">No generations yet. Run the seeders.</p>
          )}
          <ul className="space-y-2 text-sm">
            {data.generations.map((g) => (
              <li key={g.id}>
                <details>
                  <summary className="cursor-pointer font-semibold">
                    {g.name} (#{g.generation_number})
                  </summary>
                  <ul className="ml-6 mt-2 space-y-1">
                    {g.branches?.map((b) => (
                      <li key={b.id}>
                        <details>
                          <summary className="cursor-pointer">{b.name} (#{b.branch_number})</summary>
                          <ul className="ml-6 mt-1">
                            {b.teams?.map((t) => (
                              <li key={t.id}>Team {t.team_number} — {t.name}</li>
                            ))}
                            {(!b.teams || b.teams.length === 0) && (
                              <li className="text-gray-500">No teams</li>
                            )}
                          </ul>
                        </details>
                      </li>
                    ))}
                    {(!g.branches || g.branches.length === 0) && (
                      <li className="text-gray-500">No branches</li>
                    )}
                  </ul>
                </details>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
