import { getInitials } from '../lib/formatters';

const toneClasses = {
  teal: 'bg-[#d9e5df] text-teal',
  clay: 'bg-[#ead8cc] text-[#92543d]',
  olive: 'bg-[#dfe1d4] text-[#596047]',
  sand: 'bg-[#e4d9c5] text-[#80613f]',
};

export default function Avatar({ name, tone = 'teal', size = 'md', className = '' }) {
  const sizeClasses = {
    sm: 'h-9 w-9 text-[11px]',
    md: 'h-10 w-10 text-xs',
    lg: 'h-11 w-11 text-sm',
  };

  return (
    <span
      aria-hidden="true"
      className={`flex shrink-0 items-center justify-center rounded-full font-display font-bold ${sizeClasses[size]} ${toneClasses[tone] || toneClasses.teal} ${className}`}
    >
      {getInitials(name)}
    </span>
  );
}
