import { useState } from 'react';
import { ArrowRight } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/auth-context';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';

export default function LoginPage() {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    try { await login(form); navigate('/'); } catch (requestError) { setError(requestError.response?.data?.message || 'Login failed. Check your details and try again.'); } finally { setLoading(false); }
  };

  return (
    <div className="mx-auto grid min-h-[70vh] max-w-4xl items-center gap-10 lg:grid-cols-[.85fr_1.15fr]">
      <div className="hidden lg:block"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Welcome back</p><h1 className="mt-3 font-display text-4xl font-semibold leading-tight tracking-[-.06em] text-ink">Keep the right conversations close.</h1><p className="mt-4 max-w-[32ch] text-sm leading-6 text-muted">Return to your network, your communities, and the ideas worth carrying forward.</p></div>
      <Surface className="p-6 sm:p-8"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Sign in</p><h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Welcome back.</h1><p className="mt-3 text-sm leading-6 text-muted">Sign in to follow people, publish, and take part in the network.</p><form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}><Field autoComplete="email" id="login-email" label="Email" onChange={(event) => setForm({ ...form, email: event.target.value })} required type="email" value={form.email} /><Field autoComplete="current-password" id="login-password" label="Password" onChange={(event) => setForm({ ...form, password: event.target.value })} required type="password" value={form.password} />{error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}<Button className="w-full" loading={loading} type="submit">{loading ? 'Signing in…' : 'Sign in'} <ArrowRight aria-hidden="true" className="h-4 w-4" /></Button></form><p className="mt-6 text-sm text-muted">Don&apos;t have an account? <Link className="font-semibold text-teal hover:underline" to="/register">Create one</Link></p></Surface>
    </div>
  );
}
