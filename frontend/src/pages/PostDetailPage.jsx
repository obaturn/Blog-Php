import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Heart, MessageCircle } from 'lucide-react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../context/auth-context';
import { formatCount, formatRelativeTime } from '../lib/formatters';
import Avatar from '../components/Avatar';
import Button from '../components/Button';
import Field from '../components/Field';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

async function fetchPost(postId) {
  const response = await api.get(`/api/v1/posts/${postId}`);
  return response.data.data.post;
}

async function fetchComments(postId) {
  const response = await api.get(`/api/v1/posts/${postId}/comments`);
  return response.data.data.comments;
}

export default function PostDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user } = useAuth();
  const [comment, setComment] = useState('');
  const [actionError, setActionError] = useState('');
  const postQuery = useQuery({ queryKey: ['post', id], queryFn: () => fetchPost(id), retry: 1 });
  const commentsQuery = useQuery({ queryKey: ['comments', id], queryFn: () => fetchComments(id), retry: 1 });
  const likeMutation = useMutation({
    mutationFn: ({ isLiked }) => (isLiked ? api.delete(`/api/v1/posts/${id}/unlike`) : api.post(`/api/v1/posts/${id}/like`)),
    onSuccess: () => {
      setActionError('');
      queryClient.invalidateQueries({ queryKey: ['post', id] });
      queryClient.invalidateQueries({ queryKey: ['feed'] });
    },
    onError: () => setActionError('That reaction could not be saved. Please try again.'),
  });
  const commentMutation = useMutation({
    mutationFn: (body) => api.post(`/api/v1/posts/${id}/comments`, { body }),
    onSuccess: () => {
      setComment('');
      setActionError('');
      queryClient.invalidateQueries({ queryKey: ['comments', id] });
      queryClient.invalidateQueries({ queryKey: ['post', id] });
    },
    onError: () => setActionError('Your comment could not be posted. Please try again.'),
  });

  if (postQuery.isLoading) return <div aria-busy="true" className="mx-auto max-w-3xl space-y-4"><Skeleton className="h-4 w-28" /><Skeleton className="h-12 w-3/4" /><Skeleton className="h-5 w-1/3" /><Skeleton className="h-48 w-full" /><Skeleton className="h-32 w-full" /></div>;
  if (postQuery.error) return <div className="mx-auto max-w-3xl"><StatusPanel actionLabel="Return to Feed" actionTo="/" title="Unable to load this post." message="The post may have been removed or the network is unavailable." tone="error" /></div>;

  const post = postQuery.data;
  const author = post.user?.name || 'SocialBlog member';
  const title = post.title || 'Untitled post';

  return (
    <div className="mx-auto max-w-3xl">
      <Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal" to="/">← Back to Feed</Link>
      <Surface as="article" className="mt-4 overflow-hidden">
        <div className="p-6 sm:p-8">
          <div className="flex items-center gap-3"><Avatar name={author} size="lg" tone="clay" /><div><h1 className="font-display text-lg font-semibold tracking-[-.03em] text-ink">{author}</h1><p className="text-xs text-muted">{formatRelativeTime(post.created_at)} · Shared with the network</p></div></div>
          <div className="mt-8 max-w-[65ch]"><h2 className="font-display text-3xl font-semibold leading-tight tracking-[-.05em] text-ink sm:text-4xl">{title}</h2><p className="mt-5 whitespace-pre-line text-[15px] leading-7 text-[#4f5b55]">{post.content}</p></div>
        </div>
        {post.image_url && <img alt={title} className="max-h-[520px] w-full object-cover" src={post.image_url} />}
        <div className="flex items-center gap-1 border-t border-rule px-5 py-3">
          <button aria-label={`${post.is_liked ? 'Unlike' : 'Like'} ${title}`} className={`focus-ring flex min-h-11 items-center gap-2 rounded-full px-3 text-xs font-semibold ${post.is_liked ? 'bg-teal-soft text-teal' : 'text-muted hover:bg-teal-soft hover:text-teal'}`} disabled={likeMutation.isPending} onClick={() => (user ? likeMutation.mutate({ isLiked: Boolean(post.is_liked) }) : navigate('/login'))} type="button"><Heart aria-hidden="true" className="h-4 w-4" fill={post.is_liked ? 'currentColor' : 'none'} strokeWidth={1.8} />{post.is_liked ? 'Liked' : 'Like'} {formatCount(post.likes_count || 0)}</button>
          <a className="focus-ring flex min-h-11 items-center gap-2 rounded-full px-3 text-xs font-semibold text-muted hover:bg-paper hover:text-ink" href="#comments"><MessageCircle aria-hidden="true" className="h-4 w-4" strokeWidth={1.8} />Comment {formatCount(post.comments_count || 0)}</a>
        </div>
      </Surface>

      <section className="mt-8" id="comments">
        <div className="mb-4 flex items-end justify-between gap-4"><div><p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Conversation</p><h2 className="mt-2 font-display text-2xl font-semibold tracking-[-.04em] text-ink">Comments</h2></div><span className="text-sm text-muted">{formatCount(post.comments_count || commentsQuery.data?.length || 0)}</span></div>
        {actionError && <p className="mb-4 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{actionError}</p>}
        {user ? <Surface className="mb-6 p-5"><form aria-busy={commentMutation.isPending} onSubmit={(event) => { event.preventDefault(); if (comment.trim()) commentMutation.mutate(comment.trim()); }}><Field as="textarea" id="comment-body" label="Add to the conversation" maxLength={2000} onChange={(event) => setComment(event.target.value)} placeholder="What would you add?" value={comment} /><div className="mt-4 flex justify-end"><Button disabled={!comment.trim()} loading={commentMutation.isPending} type="submit">Post comment</Button></div></form></Surface> : <Surface className="mb-6 flex flex-col items-start gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-sm leading-6 text-muted">Sign in to add your perspective to this conversation.</p><Button to="/login" variant="outline">Sign in</Button></Surface>}
        {commentsQuery.isLoading && <div className="space-y-4"><Skeleton className="h-16 w-full" /><Skeleton className="h-16 w-5/6" /></div>}
        {commentsQuery.error && !commentsQuery.isLoading && <StatusPanel actionLabel="Try loading comments again" onAction={() => commentsQuery.refetch()} title="Comments could not load." tone="error" />}
        {!commentsQuery.isLoading && !commentsQuery.error && !commentsQuery.data?.length && <p className="border-t border-rule py-6 text-sm text-muted">No comments yet. Start the conversation.</p>}
        <div className="divide-y divide-rule">{commentsQuery.data?.map((item) => <div className="flex gap-3 py-5 first:pt-0" key={item.id}><Avatar name={item.user?.name} size="sm" tone="olive" /><div className="min-w-0"><div className="flex flex-wrap items-baseline gap-2"><p className="text-sm font-semibold text-ink">{item.user?.name || 'Member'}</p><span className="text-xs text-muted">{formatRelativeTime(item.created_at)}</span></div><p className="mt-2 whitespace-pre-line text-sm leading-6 text-[#4f5b55]">{item.body}</p></div></div>)}</div>
      </section>
    </div>
  );
}
