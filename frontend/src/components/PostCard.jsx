import { Ellipsis, Heart, MessageCircle } from 'lucide-react';
import { Link } from 'react-router-dom';
import Avatar from './Avatar';
import { formatCount, formatRelativeTime } from '../lib/formatters';

export default function PostCard({ post, user, onToggleLike, isLikePending, onRequireAuth }) {
  const title = post.title || 'Untitled post';
  const authorName = post.user?.name || 'SocialBlog member';
  const likeCount = post.likes_count ?? post.likesCount ?? 0;
  const commentCount = post.comments_count ?? post.commentsCount ?? 0;
  const isLiked = Boolean(post.is_liked);

  return (
    <article className="border border-rule bg-surface shadow-surface">
      <div className="p-5 pb-4 sm:p-6 sm:pb-5">
        <div className="flex items-start justify-between gap-4">
          <div className="flex min-w-0 items-center gap-3">
            <Avatar name={authorName} size="lg" tone="clay" />
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                <h2 className="font-display text-[15px] font-semibold tracking-[-.02em] text-ink">{authorName}</h2>
                <span className="text-[11px] text-muted">· {formatRelativeTime(post.created_at)}</span>
              </div>
              <p className="mt-0.5 truncate text-xs text-muted">
                {post.group?.name ? <><span className="font-semibold text-teal">{post.group.name}</span> · </> : ''}
                Shared with the network
              </p>
            </div>
          </div>
          <button aria-label={`More options for ${title}`} className="focus-ring flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-muted hover:bg-paper hover:text-ink" type="button">
            <Ellipsis aria-hidden="true" className="h-[19px] w-[19px]" strokeWidth={1.8} />
          </button>
        </div>

        <div className="mt-6 max-w-[590px]">
          {post.group?.name && <p className="mb-2 text-[10px] font-bold uppercase tracking-[.18em] text-teal">Community note</p>}
          <h3 className="font-display text-[25px] font-semibold leading-[1.1] tracking-[-.045em] text-ink sm:text-[29px]">{title}</h3>
          <p className="mt-3 line-clamp-5 text-[15px] leading-7 text-[#4f5b55]">{post.content}</p>
        </div>
      </div>

      {post.image_url && (
        <figure className="overflow-hidden border-y border-rule bg-paper">
          <img alt={title} className="max-h-[420px] w-full object-cover" decoding="async" loading="lazy" src={post.image_url} />
          <figcaption className="bg-paper px-5 py-2 text-[11px] text-muted sm:px-6">Shared image from {authorName}</figcaption>
        </figure>
      )}

      <div className="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
        <div className="flex items-center gap-1">
          <button
            aria-label={user ? `${isLiked ? 'Unlike' : 'Like'} ${title}` : 'Sign in to like this post'}
            className={`focus-ring soft-button flex min-h-11 items-center gap-2 rounded-full px-3 text-xs font-semibold transition-colors ${isLiked ? 'bg-teal-soft text-teal' : 'text-muted hover:bg-teal-soft hover:text-teal'}`}
            disabled={isLikePending}
            onClick={() => (user ? onToggleLike(post) : onRequireAuth())}
            type="button"
          >
            <Heart aria-hidden="true" className="h-[17px] w-[17px]" fill={isLiked ? 'currentColor' : 'none'} strokeWidth={1.8} />
            <span>{isLiked ? 'Liked' : 'Like'}</span>
            <span className="text-muted">{formatCount(likeCount)}</span>
          </button>
          <Link aria-label={`View ${formatCount(commentCount)} comments on ${title}`} className="focus-ring soft-button flex min-h-11 items-center gap-2 rounded-full px-3 text-xs font-semibold text-muted hover:bg-paper hover:text-ink" to={`/posts/${post.id}#comments`}>
            <MessageCircle aria-hidden="true" className="h-[17px] w-[17px]" strokeWidth={1.8} />
            <span>Comment</span>
            <span>{formatCount(commentCount)}</span>
          </Link>

        </div>
        <Link className="focus-ring hidden text-[11px] text-muted hover:text-teal sm:inline" to={`/posts/${post.id}`}>View post →</Link>
      </div>
    </article>
  );
}
