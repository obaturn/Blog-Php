export default function PageHeader({ eyebrow, title, description, action }) {
  return (
    <header className="mb-8 flex flex-col gap-5 border-b border-rule pb-6 sm:flex-row sm:items-end sm:justify-between">
      <div>
        {eyebrow && <p className="mb-2 text-[10px] font-bold uppercase tracking-[.18em] text-muted">{eyebrow}</p>}
        <h1 className="font-display text-[32px] font-semibold leading-[1.05] tracking-[-.055em] text-ink sm:text-[38px]">{title}</h1>
        {description && <p className="mt-2 max-w-[52ch] text-sm leading-6 text-muted">{description}</p>}
      </div>
      {action}
    </header>
  );
}
