import { useState } from 'react';
import { ArrowRight } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/auth-context';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';

export default function RegisterPage() {
  const navigate = useNavigate();
  const { register } = useAuth();
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    try { await register(form); navigate('/'); } catch (requestError) { setError(requestError.response?.data?.message || 'Registration failed. Please check your details and try again.'); } finally { setLoading(false); }
  };

  return (
    <div className="mx-auto grid min-h-[70vh] max-w-4xl items-center gap-10 lg:grid-cols-[.85fr_1.15fr]">
      <div className="hidden lg:block"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-clay">Make a place for your work</p><h1 className="mt-3 font-display text-4xl font-semibold leading-tight tracking-[-.06em] text-ink">A network with room to think.</h1><p className="mt-4 max-w-[32ch] text-sm leading-6 text-muted">Create a profile, find your people, and contribute to conversations that are useful beyond the scroll.</p></div>
      <Surface className="p-6 sm:p-8"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Create account</p><h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Start here.</h1><p className="mt-3 text-sm leading-6 text-muted">Join the network with the name and email people should recognize.</p><form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}><Field autoComplete="name" id="register-name" label="Name" onChange={(event) => setForm({ ...form, name: event.target.value })} required value={form.name} /><Field autoComplete="email" id="register-email" label="Email" onChange={(event) => setForm({ ...form, email: event.target.value })} required type="email" value={form.email} /><Field autoComplete="new-password" id="register-password" label="Password" onChange={(event) => setForm({ ...form, password: event.target.value })} required type="password" value={form.password} /><Field autoComplete="new-password" id="register-password-confirmation" label="Confirm password" onChange={(event) => setForm({ ...form, password_confirmation: event.target.value })} required type="password" value={form.password_confirmation} />{error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}<Button className="w-full" loading={loading} type="submit">{loading ? 'Creating account…' : 'Create account'} <ArrowRight aria-hidden="true" className="h-4 w-4" /></Button></form><p className="mt-6 text-sm text-muted">Already have an account? <Link className="font-semibold text-teal hover:underline" to="/login">Sign in</Link></p></Surface>
    </div>
  );
}
