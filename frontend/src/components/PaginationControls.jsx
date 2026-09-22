import Button from './Button';

export default function PaginationControls({ currentPage = 1, lastPage = 1, onPrevious, onNext, loading = false, label = 'results' }) {
  if (lastPage <= 1) return null;

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-rule pt-5">
      <p className="text-xs text-muted">Page {currentPage} of {lastPage} · {label}</p>
      <div className="flex items-center gap-2">
        <Button disabled={currentPage <= 1 || loading} onClick={onPrevious} size="sm" variant="outline">Previous</Button>
        <Button disabled={currentPage >= lastPage || loading} onClick={onNext} size="sm" variant="outline">Next</Button>
      </div>
    </div>
  );
}
