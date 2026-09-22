import { ArrowLeft } from 'lucide-react';
import Button from '../components/Button';
import Surface from '../components/Surface';

export default function NotFoundPage() {
  return (
    <div className="mx-auto flex min-h-[55vh] max-w-2xl items-center justify-center">
      <Surface className="w-full p-8 text-center sm:p-12">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-clay">404</p>
        <h1 className="mt-3 font-display text-3xl font-semibold tracking-[-.05em] text-ink sm:text-4xl">Page not found</h1>
        <p className="mx-auto mt-3 max-w-[42ch] text-sm leading-6 text-muted">That address does not point to a SocialBlog workspace yet. Return to your network and keep your place.</p>
        <Button className="mt-6" to="/"><ArrowLeft aria-hidden="true" className="h-4 w-4" /> Return to Feed</Button>
      </Surface>
    </div>
  );
}
