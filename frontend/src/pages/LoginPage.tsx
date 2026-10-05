import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../lib/auth';

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await login(identifier, password, remember);
      navigate('/dashboard');
    } catch (err: unknown) {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      setError(response?.data?.errors?.login?.[0] ?? response?.data?.message ?? 'Login failed.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4">
      <div className="w-full max-w-md bg-white rounded-lg shadow-card p-8">
        <div className="flex items-center gap-3 mb-6">
          <span className="inline-block h-10 w-10 rounded bg-beha-navy" />
          <div>
            <h1 className="text-xl font-bold text-gray-900">Beha</h1>
            <p className="text-xs text-gray-500">Real Estate Management System</p>
          </div>
        </div>

        <h2 className="text-lg font-semibold mb-2">Welcome back</h2>
        <p className="text-sm text-gray-500 mb-6">
          Sign in with your email, username, or Official ID.
        </p>

        {error && (
          <div className="mb-4 px-4 py-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Email or Username
            </label>
            <input
              type="text"
              value={identifier}
              onChange={(e) => setIdentifier(e.target.value)}
              required
              autoFocus
              className="input"
              placeholder="admin@beha.local"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="input"
            />
          </div>
          <label className="flex items-center text-sm text-gray-700">
            <input
              type="checkbox"
              checked={remember}
              onChange={(e) => setRemember(e.target.checked)}
              className="mr-2"
            />
            Remember me
          </label>
          <button type="submit" disabled={submitting} className="btn-primary w-full">
            {submitting ? 'Signing in…' : 'Sign in'}
          </button>
        </form>

        <div className="my-6 border-t border-gray-200" />
        <p className="text-sm text-center text-gray-600">
          New applicant?{' '}
          <a href="/apply" className="text-beha-navy font-medium hover:underline">
            Apply to join →
          </a>
        </p>
      </div>
    </div>
  );
}
