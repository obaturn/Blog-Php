import { useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';
import api from '../lib/api';

export default function ResetPasswordPage() {
  const { token } = useParams();
  const [form, setForm] = useState({ password: '', password_confirmation: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    try {
      await api.post('/api/v1/reset-password', { token, email: '', password: form.password, password_confirmation: form.password_confirmation });
      setDone(true);
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'Unable to reset password. The link may have expired.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto grid min-h-[70vh] max-w-4xl items-center gap-10 lg:grid-cols-[.85fr_1.15fr]">
      <div className="hidden lg:block"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">New password</p><h1 className="mt-3 font-display text-4xl font-semibold leading-tight tracking-[-.06em] text-ink">Choose again.</h1><p className="mt-4 max-w-[32ch] text-sm leading-6 text-muted">Pick a password you will remember. It should be strong and unique to your SocialBlog account.</p></div>
      <Surface className="p-6 sm:p-8">
        {done ? (
          <>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">All set</p>
            <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Password updated.</h1>
            <p className="mt-3 text-sm leading-6 text-muted">Your password has been changed. Sign in with your new credentials.</p>
            <div className="mt-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/login">Sign in <span aria-hidden="true" className="ml-1">→</span></Link></div>
          </>
        ) : (
          <>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Reset password</p>
            <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">New password.</h1>
            <p className="mt-3 text-sm leading-6 text-muted">Enter a new password for your account.</p>
            <form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}>
              <Field autoComplete="new-password" id="new-password" label="New password" onChange={(event) => setForm({ ...form, password: event.target.value })} required type="password" value={form.password} />
              <Field autoComplete="new-password" id="confirm-new-password" label="Confirm new password" onChange={(event) => setForm({ ...form, password_confirmation: event.target.value })} required type="password" value={form.password_confirmation} />
              {error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}
              <Button className="w-full" loading={loading} type="submit">Reset password</Button>
            </form>
          </>
        )}
      </Surface>
    </div>
  );
}
