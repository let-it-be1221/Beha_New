import { Link } from 'react-router-dom';

export function NotFoundPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="text-center">
        <h1 className="text-6xl font-bold text-beha-navy">404</h1>
        <p className="mt-4 text-gray-600">Page not found.</p>
        <Link to="/dashboard" className="btn-primary mt-6">← Back to Dashboard</Link>
      </div>
    </div>
  );
}
