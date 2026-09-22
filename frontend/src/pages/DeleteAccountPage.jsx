import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { AlertTriangle } from 'lucide-react';
import { getApiErrorMessage } from '../lib/apiErrors';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';
import api from '../lib/api';

export default function DeleteAccountPage() {
  const navigate = useNavigate();
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [step, setStep] = useState('confirm');

  const handleDelete = async (event) => {
    event.preventDefault();
    setError('');
    setLoading(true);
    try {
      await api.delete('/api/v1/account', {
        data: { password },
      });
      navigate('/');
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Unable to delete your account.'));
    } finally {
      setLoading(false);
    }
  };

  if (step === 'confirm') {
    return (
      <div className="mx-auto max-w-[640px]">
        <nav className="mb-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/profile">← Back to profile</Link></nav>
        <Surface className="p-6 sm:p-8">
          <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-clay-soft text-[#92543d]">
            <AlertTriangle aria-hidden="true" className="h-6 w-6" />
          </div>
          <p className="text-[10px] font-bold uppercase tracking-[.18em] text-clay">Danger zone</p>
          <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Delete account.</h1>
          <p className="mt-3 text-sm leading-6 text-muted">This cannot be undone. Your posts, comments, messages, and profile will be permanently removed from SocialBlog.</p>
          <div className="mt-8 flex flex-wrap items-center justify-end gap-3 border-t border-rule pt-5">
            <Button to="/profile" variant="outline">Keep my account</Button>
            <Button onClick={() => setStep('delete')} variant="clay">Delete account</Button>
          </div>
        </Surface>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-[640px]">
      <nav className="mb-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/profile">← Back to profile</Link></nav>
      <Surface className="p-6 sm:p-8">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-clay">Confirm deletion</p>
        <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Are you sure?</h1>
        <p className="mt-3 text-sm leading-6 text-muted">Enter your password to permanently delete your SocialBlog account. This action cannot be reversed.</p>
        <form className="mt-8 space-y-5" onSubmit={handleDelete}>
          <Field autoComplete="current-password" id="delete-password" label="Password" onChange={(event) => setPassword(event.target.value)} required type="password" value={password} />
          {error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}
          <div className="flex flex-wrap items-center justify-end gap-3 border-t border-rule pt-5">
            <Button onClick={() => setStep('confirm')} variant="outline">Cancel</Button>
            <Button loading={loading} variant="clay" type="submit">Permanently delete</Button>
          </div>
        </form>
      </Surface>
    </div>
  );
}
