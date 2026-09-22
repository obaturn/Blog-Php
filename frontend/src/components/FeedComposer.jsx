import { ImagePlus, UsersRound } from 'lucide-react';
import { Link } from 'react-router-dom';
import Avatar from './Avatar';
import Surface from './Surface';

export default function FeedComposer({ user }) {
  const destination = user ? '/posts/create' : '/login';
  const actionLabel = user ? 'Share an idea with your network…' : 'Sign in to share an idea…';

  return (
    <Surface aria-label="Share an idea" className="mb-7 bg-surface/70 p-4 shadow-surface sm:p-5">
      <div className="flex items-center gap-3">
        <Avatar name={user?.name || 'SocialBlog member'} />
        <Link className="focus-ring flex min-h-11 flex-1 items-center rounded-full border border-rule bg-paper-light px-4 text-left text-sm text-muted transition-colors hover:border-teal hover:text-ink" to={destination}>
          {actionLabel}
        </Link>
      </div>
      <div className="mt-4 flex items-center justify-between border-t border-rule pt-3">
        <div className="flex items-center gap-1">
          <Link aria-label="Add an image" className="focus-ring soft-button flex min-h-11 items-center gap-2 rounded-full px-3 text-xs font-semibold text-muted hover:bg-teal-soft hover:text-teal" to={destination}>
            <ImagePlus aria-hidden="true" className="h-4 w-4" strokeWidth={1.8} />
            <span>Image</span>
          </Link>
          <Link aria-label="Post to a community" className="focus-ring soft-button flex min-h-11 items-center gap-2 rounded-full px-3 text-xs font-semibold text-muted hover:bg-teal-soft hover:text-teal" to={destination}>
            <UsersRound aria-hidden="true" className="h-4 w-4" strokeWidth={1.8} />
            <span>Community</span>
          </Link>
        </div>
        <span className="hidden text-[11px] text-muted sm:inline">Visible to your network</span>
      </div>
    </Surface>
  );
}
