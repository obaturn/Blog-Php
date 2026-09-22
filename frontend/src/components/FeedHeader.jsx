export default function FeedHeader({ mode, onModeChange, user }) {
  return (
    <header className="mb-8 flex flex-col gap-5 border-b border-rule pb-6 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p className="mb-2 text-[10px] font-bold uppercase tracking-[.18em] text-muted">Tuesday · 21 September</p>
        <h1 className="font-display text-[32px] font-semibold leading-[1.05] tracking-[-.055em] text-ink sm:text-[38px]">Your network</h1>
        <p className="mt-2 max-w-[38ch] text-sm leading-6 text-muted">A considered place for the people, work, and ideas you want to keep close.</p>
      </div>
      <div aria-label="Feed view" className="flex shrink-0 items-center gap-1 rounded-full border border-rule bg-surface/70 p-1" role="tablist">
        <button
          aria-selected={mode === 'following'}
          className={`focus-ring min-h-10 rounded-full px-3.5 text-xs font-semibold transition-colors ${mode === 'following' ? 'bg-ink text-white' : 'text-muted hover:bg-teal-soft hover:text-teal'}`}
          onClick={() => user && onModeChange('following')}
          role="tab"
          type="button"
        >
          Following
        </button>
        <button
          aria-selected={mode === 'explore'}
          className={`focus-ring min-h-10 rounded-full px-3.5 text-xs font-semibold transition-colors ${mode === 'explore' ? 'bg-ink text-white' : 'text-muted hover:bg-teal-soft hover:text-teal'}`}
          onClick={() => onModeChange('explore')}
          role="tab"
          type="button"
        >
          Explore
        </button>
      </div>
    </header>
  );
}
