import { forwardRef } from 'react';

const Field = forwardRef(function Field({
  id,
  label,
  hint,
  error,
  as = 'input',
  className = '',
  children,
  ...props
}, ref) {
  const Component = as;
  const describedBy = [hint && `${id}-hint`, error && `${id}-error`].filter(Boolean).join(' ') || undefined;

  return (
    <div className="space-y-2">
      <label className="block text-sm font-semibold text-ink" htmlFor={id}>
        {label}
      </label>
      {Component === 'textarea' ? (
        <textarea
          aria-describedby={describedBy}
          aria-invalid={Boolean(error)}
          className={`min-h-32 w-full resize-y border border-rule bg-surface px-4 py-3 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-teal focus:outline-none ${className}`}
          id={id}
          ref={ref}
          {...props}
        />
      ) : Component === 'select' ? (
        <select
          aria-describedby={describedBy}
          aria-invalid={Boolean(error)}
          className={`min-h-11 w-full border border-rule bg-surface px-4 text-sm text-ink transition-colors focus:border-teal focus:outline-none ${className}`}
          id={id}
          ref={ref}
          {...props}
        >
          {children}
        </select>
      ) : (
        <input
          aria-describedby={describedBy}
          aria-invalid={Boolean(error)}
          className={`min-h-11 w-full border border-rule bg-surface px-4 text-sm text-ink placeholder:text-muted/70 transition-colors focus:border-teal focus:outline-none ${className}`}
          id={id}
          ref={ref}
          {...props}
        />
      )}
      {hint && <p className="text-xs leading-5 text-muted" id={`${id}-hint`}>{hint}</p>}
      {error && <p className="text-xs leading-5 text-clay" id={`${id}-error`} role="alert">{error}</p>}
    </div>
  );
});

export default Field;
