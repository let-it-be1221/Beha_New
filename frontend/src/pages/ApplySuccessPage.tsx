import { Link, useSearchParams } from 'react-router-dom';
import { CheckCircle2 } from 'lucide-react';

export function ApplySuccessPage() {
  const [params] = useSearchParams();
  const code = params.get('code') ?? '';

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100 px-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-card p-8 text-center">
        <div className="flex justify-center mb-4">
          <div className="rounded-full bg-green-100 p-3">
            <CheckCircle2 className="h-8 w-8 text-green-600" />
          </div>
        </div>
        <h1 className="text-2xl font-bold mb-2">Application Submitted!</h1>
        <p className="text-sm text-gray-500 mb-4">
          Thank you for applying. A Team Leader will review your application and reach out to you soon.
        </p>
        {code && (
          <div className="px-4 py-3 rounded bg-beha-navy/5 border border-beha-navy/20 mb-4">
            <p className="text-xs text-gray-500 uppercase tracking-wide mb-1">Your reference code</p>
            <p className="font-mono text-lg font-bold text-beha-navy">{code}</p>
            <p className="text-xs text-gray-500 mt-1">Keep this code for your records.</p>
          </div>
        )}
        <Link to="/" className="btn-primary">Back to home</Link>
      </div>
    </div>
  );
}
