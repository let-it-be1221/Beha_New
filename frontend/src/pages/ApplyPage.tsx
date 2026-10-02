import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { http, fetchCsrfToken } from '../lib/api';
import { Check, ChevronLeft, ChevronRight, GraduationCap, IdCard, Mail, Phone, User, Briefcase, FileText } from 'lucide-react';

interface FormData {
  full_name: string;
  email: string;
  phone: string;
  national_id: string;
  address: string;
  education: { institution: string; degree: string; year: string }[];
  experience: { company: string; role: string; years: string }[];
  references: { name: string; phone: string; email: string }[];
  terms_accepted: boolean;
}

const EMPTY: FormData = {
  full_name: '', email: '', phone: '', national_id: '', address: '',
  education: [{ institution: '', degree: '', year: '' }],
  experience: [{ company: '', role: '', years: '' }],
  references: [{ name: '', phone: '', email: '' }],
  terms_accepted: false,
};

const STEPS = ['Personal', 'Contact', 'Education', 'Experience', 'References', 'Review'];

export function ApplyPage() {
  const navigate = useNavigate();
  const [step, setStep] = useState(0);
  const [form, setForm] = useState<FormData>(EMPTY);
  const [error, setError] = useState<string | null>(null);

  const mutation = useMutation({
    mutationFn: async (payload: Record<string, unknown>) => {
      // Sanctum CSRF cookie is required before POST
      await fetchCsrfToken();
      return http.post('/applicants/apply', payload);
    },
    onSuccess: (response) => {
      const code = response.data?.applicant?.application_code ?? '';
      navigate(`/apply/success?code=${code}`);
    },
    onError: (err: unknown) => {
      const response = (err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response;
      const firstError = response?.data?.errors
        ? Object.values(response.data.errors)[0]?.[0]
        : undefined;
      setError(firstError ?? response?.data?.message ?? 'Failed to submit application.');
    },
  });

  const handleSubmit = () => {
    setError(null);
    // Strip empty education/experience/reference rows
    const cleaned = {
      ...form,
      education: form.education.filter((e) => e.institution),
      experience: form.experience.filter((e) => e.company),
      references: form.references.filter((r) => r.name),
    };
    mutation.mutate(cleaned);
  };

  const canProceed = () => {
    switch (step) {
      case 0: return form.full_name.trim() !== '' && form.national_id.trim() !== '';
      case 1: return form.email.trim() !== '' && form.phone.trim() !== '';
      case 5: return form.terms_accepted;
      default: return true;
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 py-12 px-4">
      <div className="max-w-3xl mx-auto">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center gap-2 mb-3">
            <span className="inline-block h-10 w-10 rounded bg-beha-navy" />
            <h1 className="text-2xl font-bold">Beha</h1>
          </div>
          <h2 className="text-xl font-semibold">Join our organization</h2>
          <p className="text-sm text-gray-500 mt-1">
            Submit your application — a Team Leader will review and reach out within a few days.
          </p>
        </div>

        {/* Stepper */}
        <div className="mb-6">
          <div className="flex items-center justify-between">
            {STEPS.map((label, i) => (
              <div key={label} className="flex items-center flex-1 last:flex-none">
                <div
                  className={`flex items-center justify-center h-8 w-8 rounded-full text-xs font-semibold ${
                    i < step ? 'bg-green-100 text-green-700'
                    : i === step ? 'bg-beha-navy text-white'
                    : 'bg-gray-100 text-gray-400'
                  }`}
                >
                  {i < step ? <Check className="h-4 w-4" /> : i + 1}
                </div>
                {i < STEPS.length - 1 && (
                  <div className={`flex-1 h-0.5 mx-2 ${i < step ? 'bg-green-200' : 'bg-gray-200'}`} />
                )}
              </div>
            ))}
          </div>
          <p className="mt-3 text-center text-sm font-medium text-gray-700">
            Step {step + 1} of {STEPS.length}: {STEPS[step]}
          </p>
        </div>

        {/* Card */}
        <div className="card p-6">
          {error && (
            <div className="mb-4 px-4 py-3 rounded bg-red-50 border border-red-200 text-red-800 text-sm">
              {error}
            </div>
          )}

          {/* Step 0 — Personal */}
          {step === 0 && (
            <div className="space-y-4">
              <Field icon={User} label="Full Name *" required>
                <input value={form.full_name} onChange={(e) => setForm({ ...form, full_name: e.target.value })} className="input" placeholder="John Doe" />
              </Field>
              <Field icon={IdCard} label="National ID *" required>
                <input value={form.national_id} onChange={(e) => setForm({ ...form, national_id: e.target.value })} className="input" placeholder="ID-1234567" />
              </Field>
              <Field icon={FileText} label="Address">
                <textarea value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} className="input min-h-[60px]" placeholder="Street, City, Country" />
              </Field>
            </div>
          )}

          {/* Step 1 — Contact */}
          {step === 1 && (
            <div className="space-y-4">
              <Field icon={Mail} label="Email *" required>
                <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="input" placeholder="you@example.com" />
              </Field>
              <Field icon={Phone} label="Phone *" required>
                <input type="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="input" placeholder="+251 9XX XXX XXX" />
              </Field>
            </div>
          )}

          {/* Step 2 — Education */}
          {step === 2 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold flex items-center gap-2"><GraduationCap className="h-4 w-4" /> Education History</h3>
                <button type="button" onClick={() => setForm({ ...form, education: [...form.education, { institution: '', degree: '', year: '' }] })} className="btn-secondary text-xs">
                  + Add row
                </button>
              </div>
              {form.education.map((edu, i) => (
                <div key={i} className="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 border border-gray-200 rounded">
                  <input value={edu.institution} onChange={(e) => { const arr = [...form.education]; arr[i].institution = e.target.value; setForm({ ...form, education: arr }); }} placeholder="Institution" className="input" />
                  <input value={edu.degree} onChange={(e) => { const arr = [...form.education]; arr[i].degree = e.target.value; setForm({ ...form, education: arr }); }} placeholder="Degree" className="input" />
                  <input type="number" min="1950" max={new Date().getFullYear()} value={edu.year} onChange={(e) => { const arr = [...form.education]; arr[i].year = e.target.value; setForm({ ...form, education: arr }); }} placeholder="Year" className="input" />
                </div>
              ))}
            </div>
          )}

          {/* Step 3 — Experience */}
          {step === 3 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold flex items-center gap-2"><Briefcase className="h-4 w-4" /> Work Experience</h3>
                <button type="button" onClick={() => setForm({ ...form, experience: [...form.experience, { company: '', role: '', years: '' }] })} className="btn-secondary text-xs">
                  + Add row
                </button>
              </div>
              {form.experience.map((exp, i) => (
                <div key={i} className="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 border border-gray-200 rounded">
                  <input value={exp.company} onChange={(e) => { const arr = [...form.experience]; arr[i].company = e.target.value; setForm({ ...form, experience: arr }); }} placeholder="Company" className="input" />
                  <input value={exp.role} onChange={(e) => { const arr = [...form.experience]; arr[i].role = e.target.value; setForm({ ...form, experience: arr }); }} placeholder="Role" className="input" />
                  <input type="number" min="0" max="60" value={exp.years} onChange={(e) => { const arr = [...form.experience]; arr[i].years = e.target.value; setForm({ ...form, experience: arr }); }} placeholder="Years" className="input" />
                </div>
              ))}
            </div>
          )}

          {/* Step 4 — References */}
          {step === 4 && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="font-semibold flex items-center gap-2"><User className="h-4 w-4" /> References</h3>
                <button type="button" onClick={() => setForm({ ...form, references: [...form.references, { name: '', phone: '', email: '' }] })} className="btn-secondary text-xs">
                  + Add row
                </button>
              </div>
              {form.references.map((ref, i) => (
                <div key={i} className="grid grid-cols-1 md:grid-cols-3 gap-2 p-3 border border-gray-200 rounded">
                  <input value={ref.name} onChange={(e) => { const arr = [...form.references]; arr[i].name = e.target.value; setForm({ ...form, references: arr }); }} placeholder="Name" className="input" />
                  <input value={ref.phone} onChange={(e) => { const arr = [...form.references]; arr[i].phone = e.target.value; setForm({ ...form, references: arr }); }} placeholder="Phone" className="input" />
                  <input type="email" value={ref.email} onChange={(e) => { const arr = [...form.references]; arr[i].email = e.target.value; setForm({ ...form, references: arr }); }} placeholder="Email" className="input" />
                </div>
              ))}
            </div>
          )}

          {/* Step 5 — Review */}
          {step === 5 && (
            <div className="space-y-4">
              <h3 className="font-semibold">Review your application</h3>
              <dl className="text-sm space-y-2">
                <ReviewRow label="Full Name" value={form.full_name} />
                <ReviewRow label="National ID" value={form.national_id} />
                <ReviewRow label="Email" value={form.email} />
                <ReviewRow label="Phone" value={form.phone} />
                <ReviewRow label="Education entries" value={String(form.education.filter((e) => e.institution).length)} />
                <ReviewRow label="Experience entries" value={String(form.experience.filter((e) => e.company).length)} />
                <ReviewRow label="References" value={String(form.references.filter((r) => r.name).length)} />
              </dl>
              <label className="flex items-start gap-2 text-sm">
                <input type="checkbox" checked={form.terms_accepted} onChange={(e) => setForm({ ...form, terms_accepted: e.target.checked })} className="mt-1" />
                <span>
                  I confirm that the information provided is accurate and I accept the
                  organization's <a href="#" className="text-beha-navy hover:underline">terms and conditions</a>.
                </span>
              </label>
            </div>
          )}

          {/* Footer buttons */}
          <div className="flex justify-between mt-6">
            <button
              type="button"
              onClick={() => setStep(Math.max(0, step - 1))}
              disabled={step === 0}
              className="btn-secondary disabled:opacity-50"
            >
              <ChevronLeft className="h-4 w-4 mr-1 inline" /> Back
            </button>
            {step < STEPS.length - 1 ? (
              <button
                type="button"
                onClick={() => setStep(step + 1)}
                disabled={!canProceed()}
                className="btn-primary disabled:opacity-50"
              >
                Next <ChevronRight className="h-4 w-4 ml-1 inline" />
              </button>
            ) : (
              <button
                type="button"
                onClick={handleSubmit}
                disabled={!canProceed() || mutation.isPending}
                className="btn-primary disabled:opacity-50"
              >
                {mutation.isPending ? 'Submitting…' : 'Submit Application'}
              </button>
            )}
          </div>
        </div>

        <p className="text-center mt-4 text-sm">
          Already have an account?{' '}
          <a href="/login" className="text-beha-navy font-medium hover:underline">Sign in</a>
        </p>
      </div>
    </div>
  );
}

function Field({ icon: Icon, label, children, required }: { icon: React.ComponentType<{ className?: string }>; label: string; required?: boolean; children: React.ReactNode }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">
        <Icon className="h-4 w-4 inline mr-1" />
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </label>
      {children}
    </div>
  );
}

function ReviewRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between border-b border-gray-100 pb-1">
      <dt className="text-gray-500">{label}</dt>
      <dd className="font-medium">{value || '—'}</dd>
    </div>
  );
}
