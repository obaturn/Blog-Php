import Button from './Button';
import Surface from './Surface';

export default function StatusPanel({
  eyebrow,
  title,
  message,
  actionLabel,
  onAction,
  actionTo,
  tone = 'neutral',
  className = '',
}) {
  const toneClasses = {
    neutral: 'text-muted',
    error: 'text-clay',
    accent: 'text-teal',
  };

  return (
    <Surface className={`p-6 sm:p-8 ${className}`}>
      {eyebrow && <p className={`mb-2 text-[10px] font-bold uppercase tracking-[.18em] ${toneClasses[tone]}`}>{eyebrow}</p>}
      <h2 className="font-display text-xl font-semibold tracking-[-.03em] text-ink">{title}</h2>
      {message && <p className="mt-2 max-w-[48ch] text-sm leading-6 text-muted">{message}</p>}
      {actionLabel && (
        <Button className="mt-5" onClick={onAction} to={actionTo} variant={tone === 'error' ? 'outline' : 'primary'}>
          {actionLabel}
        </Button>
      )}
    </Surface>
  );
}
