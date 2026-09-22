import { PostSkeleton } from './Skeleton';

export default function FeedSkeleton() {
  return (
    <div aria-busy="true" aria-label="Loading feed" className="space-y-6">
      <PostSkeleton />
      <PostSkeleton />
    </div>
  );
}
