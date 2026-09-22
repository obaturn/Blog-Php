import { useState } from 'react';
import { Link } from 'react-router-dom';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';
import api from '../lib/api';

export default function ForgotPasswordPage() {
  const [form, setForm] = useState({ email: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    try {
      await api.post('/api/v1/forgot-password', { email: form.email });
      setSubmitted(true);
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'Unable to send reset link. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto grid min-h-[70vh] max-w-4xl items-center gap-10 lg:grid-cols-[.85fr_1.15fr]">
      <div className="hidden lg:block"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Reset password</p><h1 className="mt-3 font-display text-4xl font-semibold leading-tight tracking-[-.06em] text-ink">Get back in.</h1><p className="mt-4 max-w-[32ch] text-sm leading-6 text-muted">Your reset link is on its way. Check your inbox and follow the link to choose a new password.</p></div>
      <Surface className="p-6 sm:p-8">
        {submitted ? (
          <>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Check your inbox</p>
            <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Reset link sent.</h1>
            <p className="mt-3 text-sm leading-6 text-muted">If that email is registered, you will receive a password reset link shortly. It expires in 60 minutes.</p>
            <div className="mt-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/login">Return to sign in <span aria-hidden="true" className="ml-1">→</span></Link></div>
          </>
        ) : (
          <>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Forgot password</p>
            <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Reset password.</h1>
            <p className="mt-3 text-sm leading-6 text-muted">Enter the email address you used to register. We will send you a reset link.</p>
            <form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}>
              <Field autoComplete="email" id="reset-email" label="Email" onChange={(event) => setForm({ ...form, email: event.target.value })} required type="email" value={form.email} />
              {error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}
              <Button className="w-full" loading={loading} type="submit">Send reset link</Button>
            </form>
            <p className="mt-6 text-sm text-muted">Remember your password? <Link className="font-semibold text-teal hover:underline" to="/login">Sign in</Link></p>
          </>
        )}
      </Surface>
    </div>
  );
}
