import { useMemo, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Link, useNavigate } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../context/auth-context';
import { feedQueryKey, useFeed } from '../hooks/useFeed';
import Button from '../components/Button';
import FeedComposer from '../components/FeedComposer';
import FeedHeader from '../components/FeedHeader';
import FeedSkeleton from '../components/FeedSkeleton';
import PostCard from '../components/PostCard';
import StatusPanel from '../components/StatusPanel';

export default function FeedPage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [mode, setMode] = useState(null);
  const [actionError, setActionError] = useState('');
  const activeMode = user ? mode || 'following' : 'explore';
  const feedQuery = useFeed({ mode: activeMode, user });

  const likeMutation = useMutation({
    mutationFn: ({ postId, isLiked }) => (
      isLiked ? api.delete(`/api/v1/posts/${postId}/unlike`) : api.post(`/api/v1/posts/${postId}/like`)
    ),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: feedQueryKey(activeMode, user?.id) });
    },
    onError: () => setActionError('That reaction could not be saved. Please try again.'),
  });

  const posts = useMemo(
    () => feedQuery.data?.pages?.flatMap((page) => page.posts || []) || [],
    [feedQuery.data],
  );

  const handleModeChange = (nextMode) => {
    if (nextMode === 'following' && !user) {
      navigate('/login');
      return;
    }
    setActionError('');
    setMode(nextMode);
  };

  const handleToggleLike = (post) => {
    setActionError('');
    likeMutation.mutate({ postId: post.id, isLiked: Boolean(post.is_liked) });
  };

  return (
    <div className="mx-auto max-w-[720px]">
      <FeedHeader mode={activeMode} onModeChange={handleModeChange} user={user} />
      <FeedComposer user={user} />
      {actionError && <p className="mb-5 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{actionError}</p>}

      {feedQuery.isLoading && <FeedSkeleton />}

      {feedQuery.error && !feedQuery.isLoading && (
        <StatusPanel
          actionLabel="Try loading the feed again"
          onAction={() => feedQuery.refetch()}
          title="Unable to load the feed."
          message="The network could not be reached just now. Your place is still here when you are ready to try again."
          tone="error"
        />
      )}

      {!feedQuery.isLoading && !feedQuery.error && !posts.length && (
        <StatusPanel
          actionLabel={activeMode === 'following' ? 'Explore the wider network' : user ? 'Share an idea' : 'Sign in to publish'}
          actionTo={activeMode === 'following' ? undefined : user ? '/posts/create' : '/login'}
          onAction={activeMode === 'following' ? () => handleModeChange('explore') : undefined}
          title={activeMode === 'following' ? 'No posts from your network yet.' : 'There are no posts here yet.'}
          message={activeMode === 'following' ? 'Explore the wider network to find your next conversation, then follow the people whose work you want to keep close.' : 'Be the first person to share something useful with this network.'}
          tone="accent"
        />
      )}

      {!feedQuery.isLoading && !feedQuery.error && posts.length > 0 && (
        <div className="space-y-6">
          {posts.map((post) => (
            <PostCard
              isLikePending={likeMutation.isPending && likeMutation.variables?.postId === post.id}
              key={post.id}
              onRequireAuth={() => navigate('/login')}
              onToggleLike={handleToggleLike}
              post={post}
              user={user}
            />
          ))}
          <div className="flex items-center justify-between gap-4 border border-dashed border-rule bg-paper px-4 py-3 text-xs text-muted sm:px-5">
            <span>
              <span className="font-semibold text-ink">{feedQuery.hasNextPage ? 'Keep going.' : 'Your feed is caught up.'}</span>{' '}
              {feedQuery.hasNextPage ? 'There is more from your network.' : 'More from the wider network is ready.'}
            </span>
            {feedQuery.hasNextPage ? (
              <Button loading={feedQuery.isFetchingNextPage} onClick={() => feedQuery.fetchNextPage()} size="sm" variant="quiet">Load older posts</Button>
            ) : (
              <button className="focus-ring min-h-11 rounded-full px-3 font-semibold text-teal hover:bg-teal-soft" onClick={() => handleModeChange('explore')} type="button">Explore posts <span aria-hidden="true">→</span></button>
            )}
          </div>
        </div>
      )}

      {!user && (
        <p className="mt-7 text-center text-xs leading-5 text-muted">Reading publicly. <Link className="font-semibold text-teal hover:underline" to="/login">Sign in</Link> to follow people and shape your network.</p>
      )}
    </div>
  );
}
