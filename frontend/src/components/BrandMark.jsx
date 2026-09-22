export default function BrandMark({ compact = false }) {
  return (
    <span className={`flex items-center ${compact ? 'gap-2' : 'gap-2.5'}`}>
      <span className={`relative flex items-center justify-center rounded-[10px] bg-clay font-display font-bold text-white after:absolute after:-right-1 after:-top-1 after:h-2 after:w-2 after:rounded-full after:bg-teal ${compact ? 'h-7 w-7 text-xs' : 'h-8 w-8 text-xs'}`}>
        S
      </span>
      <span className={`font-display font-bold tracking-[-.055em] text-ink ${compact ? 'text-lg' : 'text-xl'}`}>
        SocialBlog
      </span>
    </span>
  );
}
