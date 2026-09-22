export default function Surface({ as: Element = 'div', className = '', children, ...props }) {
  return (
    <Element className={`border border-rule bg-surface ${className}`} {...props}>
      {children}
    </Element>
  );
}
