import { useState } from 'react';
import { ArrowLeft, ImagePlus } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../lib/api';
import Button from '../components/Button';
import Field from '../components/Field';
import Surface from '../components/Surface';

export default function CreatePostPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ title: '', content: '' });
  const [image, setImage] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    const payload = new FormData();
    payload.append('title', form.title);
    payload.append('content', form.content);
    if (image) payload.append('image', image);

    try {
      await api.post('/api/v1/posts', payload, { headers: { 'Content-Type': 'multipart/form-data' } });
      navigate('/');
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'Unable to publish this post yet.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto max-w-2xl">
      <Link className="focus-ring inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-teal" to="/"><ArrowLeft aria-hidden="true" className="h-4 w-4" /> Back to Feed</Link>
      <Surface className="mt-4 p-6 sm:p-8">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Publish to your network</p>
        <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Create a post</h1>
        <p className="mt-3 max-w-[52ch] text-sm leading-6 text-muted">Share a clear idea, a useful lesson, or a question your community can help you think through.</p>
        <form aria-busy={loading} className="mt-8 space-y-5" onSubmit={handleSubmit}>
          <Field id="post-title" label="Title" onChange={(event) => setForm({ ...form, title: event.target.value })} placeholder="Give the idea a clear name" required value={form.title} />
          <Field as="textarea" className="min-h-48" id="post-content" label="Content" onChange={(event) => setForm({ ...form, content: event.target.value })} placeholder="What do you want people to take away?" required value={form.content} />
          <div className="space-y-2"><label className="block text-sm font-semibold text-ink" htmlFor="post-image">Image <span className="font-normal text-muted">(optional)</span></label><label className="flex min-h-24 cursor-pointer items-center gap-3 border border-dashed border-rule bg-paper px-4 text-sm text-muted transition-colors hover:border-teal hover:bg-teal-soft/40" htmlFor="post-image"><ImagePlus aria-hidden="true" className="h-5 w-5 text-teal" /><span>{image ? image.name : 'Choose a supporting image'}</span><input accept="image/*" className="sr-only" id="post-image" onChange={(event) => setImage(event.target.files?.[0] || null)} type="file" /></label></div>
          {error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}
          <Button loading={loading} type="submit">{loading ? 'Publishing…' : 'Publish post'}</Button>
        </form>
      </Surface>
    </div>
  );
}
