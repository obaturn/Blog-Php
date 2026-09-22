import { useState } from 'react';
import { Link } from 'react-router-dom';
import { getApiErrorMessage, getApiFieldErrors } from '../lib/apiErrors';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';
import api from '../lib/api';

export default function ChangePasswordPage() {
  const [form, setForm] = useState({ current_password: '', password: '', password_confirmation: '' });
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);

  const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setFieldErrors({});
    setSuccess(false);
    setLoading(true);
    try {
      await api.post('/api/v1/password/change', {
        current_password: form.current_password,
        password: form.password,
        password_confirmation: form.password_confirmation,
      });
      setSuccess(true);
      setForm({ current_password: '', password: '', password_confirmation: '' });
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Unable to change password.'));
      setFieldErrors(getApiFieldErrors(requestError));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto max-w-[640px]">
      <nav className="mb-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/profile">← Back to profile</Link></nav>
      <Surface className="p-6 sm:p-8">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Security</p>
        <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Change password.</h1>
        <p className="mt-3 text-sm leading-6 text-muted">Keep your account secure with a strong, up-to-date password.</p>
        {success && <p className="mb-5 border-l-2 border-teal bg-teal-soft px-3 py-2 text-sm text-teal" role="status">Password changed successfully. Use your new password next time you sign in.</p>}
        <form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}>
          <Field autoComplete="current-password" error={fieldErrors?.current_password?.[0]} id="current-password" label="Current password" onChange={(event) => update('current_password', event.target.value)} required type="password" value={form.current_password} />
          <Field autoComplete="new-password" error={fieldErrors?.password?.[0]} id="new-password" label="New password" onChange={(event) => update('password', event.target.value)} required type="password" value={form.password} />
          <Field autoComplete="new-password" error={fieldErrors?.password_confirmation?.[0]} id="confirm-new-password" label="Confirm new password" onChange={(event) => update('password_confirmation', event.target.value)} required type="password" value={form.password_confirmation} />
          {error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}
          <div className="flex flex-wrap items-center justify-end gap-3 border-t border-rule pt-5">
            <Button to="/profile" variant="outline">Cancel</Button>
            <Button loading={loading} type="submit">Update password</Button>
          </div>
        </form>
      </Surface>
    </div>
  );
}
