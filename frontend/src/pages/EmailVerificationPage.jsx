import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { CheckCircle2, XCircle, RefreshCw } from 'lucide-react';
import Button from '../components/Button';
import Surface from '../components/Surface';
import api from '../lib/api';

export default function EmailVerificationPage() {
  const { id, hash } = useParams();
  const [status, setStatus] = useState('verifying');
  const [message, setMessage] = useState('');
  const [resending, setResending] = useState(false);

  useEffect(() => {
    let cancelled = false;
    api.get(`/api/v1/email/verify/${id}/${hash}`)
      .then(() => {
        if (cancelled) return;
        setStatus('verified');
        setMessage('Your email address has been verified. Welcome to the network.');
      })
      .catch((error) => {
        if (cancelled) return;
        setStatus('error');
        setMessage(error.response?.data?.message || 'The verification link is invalid or has expired.');
      });
    return () => { cancelled = true; };
  }, [id, hash]);

  const resend = async () => {
    setResending(true);
    try {
      await api.post('/api/v1/email/verification-notification');
      setMessage('A new verification email has been sent.');
    } catch (error) {
      setMessage(error.response?.data?.message || 'Unable to resend verification email.');
    } finally {
      setResending(false);
    }
  };

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-xl items-center justify-center px-5">
      <Surface className="w-full p-8 text-center sm:p-12">
        {status === 'verifying' ? (
          <>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Verification</p>
            <h1 className="mt-4 font-display text-2xl font-semibold tracking-[-.04em] text-ink">Verifying your email…</h1>
            <p className="mt-3 text-sm leading-6 text-muted">Please wait while we confirm your account.</p>
          </>
        ) : status === 'verified' ? (
          <>
            <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-teal-soft text-teal">
              <CheckCircle2 aria-hidden="true" className="h-6 w-6" strokeWidth={1.8} />
            </div>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Verified</p>
            <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.04em] text-ink">Email verified.</h1>
            <p className="mx-auto mt-3 max-w-[42ch] text-sm leading-6 text-muted">{message}</p>
            <div className="mt-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/">Go to feed <span aria-hidden="true">→</span></Link></div>
          </>
        ) : (
          <>
            <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-clay-soft text-[#92543d]">
              <XCircle aria-hidden="true" className="h-6 w-6" strokeWidth={1.8} />
            </div>
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-clay">Verification failed</p>
            <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.04em] text-ink">Link expired.</h1>
            <p className="mx-auto mt-3 max-w-[42ch] text-sm leading-6 text-muted">{message}</p>
            <div className="mt-6 flex flex-col items-center gap-3">
              <Button onClick={resend} loading={resending} variant="outline"><RefreshCw aria-hidden="true" className="h-4 w-4" /> Resend verification</Button>
              <Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/login">Go to sign in <span aria-hidden="true">→</span></Link>
            </div>
          </>
        )}
      </Surface>
    </div>
  );
}
