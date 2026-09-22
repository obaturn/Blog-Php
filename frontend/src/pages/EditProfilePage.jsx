import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/auth-context';
import { getApiErrorMessage, getApiFieldErrors } from '../lib/apiErrors';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';
import api from '../lib/api';

export default function EditProfilePage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [form, setForm] = useState({
    name: user?.name || '',
    email: user?.email || '',
    bio: '',
  });
  const [error, setError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setFieldErrors({});
    setLoading(true);
    try {
      await api.post('/api/v1/profile', {
        name: form.name.trim() || undefined,
        email: form.email.trim() || undefined,
        bio: form.bio.trim() || undefined,
      });
      navigate('/profile');
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Unable to update profile.'));
      setFieldErrors(getApiFieldErrors(requestError));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto max-w-[640px]">
      <nav className="mb-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/profile">← Back to profile</Link></nav>
      <Surface className="p-6 sm:p-8">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Account</p>
        <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Edit profile.</h1>
        <p className="mt-3 text-sm leading-6 text-muted">Update the details people see when they visit your profile or find you in the network.</p>
        <form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}>
          <Field error={fieldErrors?.name?.[0]} id="profile-name" label="Name" onChange={(event) => update('name', event.target.value)} required value={form.name} />
          <Field error={fieldErrors?.email?.[0]} id="profile-email" label="Email" onChange={(event) => update('email', event.target.value)} required type="email" value={form.email} />
          <Field as="textarea" error={fieldErrors?.bio?.[0]} hint="A short line about your work or interests." id="profile-bio" label="Bio" onChange={(event) => update('bio', event.target.value)} placeholder="What do you focus on?" value={form.bio} />
          {error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}
          <div className="flex flex-wrap items-center justify-end gap-3 border-t border-rule pt-5">
            <Button to="/profile" variant="outline">Cancel</Button>
            <Button loading={loading} type="submit">Save changes</Button>
          </div>
        </form>
      </Surface>
    </div>
  );
}
