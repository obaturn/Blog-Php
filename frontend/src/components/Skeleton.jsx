export function Skeleton({ className = '' }) {
  return <div aria-hidden="true" className={`animate-pulse bg-rule/60 ${className}`} />;
}

export default Skeleton;

export function PostSkeleton() {
  return (
    <div aria-hidden="true" className="border border-rule bg-surface p-5 sm:p-6">
      <div className="flex items-center gap-3">
        <Skeleton className="h-11 w-11 rounded-full" />
        <div className="space-y-2">
          <Skeleton className="h-3 w-32" />
          <Skeleton className="h-3 w-24" />
        </div>
      </div>
      <div className="mt-7 space-y-3">
        <Skeleton className="h-3 w-28" />
        <Skeleton className="h-8 w-3/4" />
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-5/6" />
      </div>
      <Skeleton className="mt-6 h-48 w-full" />
      <div className="mt-4 flex gap-2">
        <Skeleton className="h-11 w-24 rounded-full" />
        <Skeleton className="h-11 w-28 rounded-full" />
      </div>
    </div>
  );
}
