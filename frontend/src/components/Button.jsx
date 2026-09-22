import { LoaderCircle } from 'lucide-react';
import { Link } from 'react-router-dom';

const variantClasses = {
  primary: 'bg-teal text-white hover:bg-[#0b5d56] disabled:bg-teal/50',
  ink: 'bg-ink text-white hover:bg-[#17201e] disabled:bg-ink/50',
  outline: 'border border-rule bg-transparent text-teal hover:border-teal hover:bg-teal-soft disabled:border-rule disabled:text-muted/60',
  quiet: 'text-muted hover:bg-paper hover:text-ink disabled:text-muted/50',
  clay: 'bg-clay text-white hover:bg-[#c56849] disabled:bg-clay/50',
};

export default function Button({
  children,
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  className = '',
  to,
  type = 'button',
  ...props
}) {
  const classes = `soft-button focus-ring inline-flex min-h-11 items-center justify-center gap-2 rounded-full px-4 text-sm font-semibold transition-colors ${size === 'sm' ? 'px-3 text-xs' : ''} ${variantClasses[variant] || variantClasses.primary} ${className}`;
  const content = (
    <>
      {loading && <LoaderCircle aria-hidden="true" className="h-4 w-4 animate-spin" />}
      {children}
    </>
  );

  if (to) {
    return <Link className={classes} to={to} {...props}>{content}</Link>;
  }

  return (
    <button className={classes} disabled={disabled || loading} type={type} {...props}>
      {content}
    </button>
  );
}
