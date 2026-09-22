export default function IconButton({ label, children, className = '', type = 'button', ...props }) {
  return (
    <button
      aria-label={label}
      className={`focus-ring soft-button flex h-11 w-11 items-center justify-center rounded-full text-muted transition-colors hover:bg-teal-soft hover:text-teal ${className}`}
      type={type}
      {...props}
    >
      {children}
    </button>
  );
}
