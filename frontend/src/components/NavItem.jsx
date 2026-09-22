import { NavLink } from 'react-router-dom';

export default function NavItem({ item, onNavigate }) {
  const Icon = item.icon;

  return (
    <NavLink
      className={({ isActive }) => (
        `nav-item focus-ring flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm transition-colors ${isActive
          ? 'bg-teal font-semibold text-white shadow-[0_8px_18px_rgba(15,107,99,.18)]'
          : 'font-medium text-muted hover:bg-teal-soft hover:text-teal'}`
      )}
      end={item.to === '/'}
      onClick={onNavigate}
      to={item.to}
    >
      <Icon aria-hidden="true" className="h-[17px] w-[17px]" strokeWidth={1.8} />
      <span>{item.label}</span>
      {item.showUnread && <span aria-label="Unread messages" className="ml-auto h-2 w-2 rounded-full bg-clay" />}
      {item.badge > 0 && <span aria-label={`${item.badge} unread`} className="ml-auto rounded-full bg-clay px-2 py-0.5 text-[10px] font-bold text-white">{item.badge > 99 ? '99+' : item.badge}</span>}
    </NavLink>
  );
}
