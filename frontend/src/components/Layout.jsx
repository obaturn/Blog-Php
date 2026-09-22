import { LogIn, LogOut, Menu, Search, X } from 'lucide-react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useState } from 'react';
import { useAuth } from '../context/auth-context';
import { useUnreadMessageCount } from '../hooks/useMessaging';
import { useUnreadNotificationCount } from '../hooks/useNotifications';
import { accountNavigation, workspaceNavigation } from '../lib/navigation';
import BrandMark from './BrandMark';
import IconButton from './IconButton';
import NavItem from './NavItem';
import UtilityRail from './UtilityRail';
import Avatar from './Avatar';

export default function Layout() {
  const { user, logout } = useAuth();
  const location = useLocation();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const isFeed = location.pathname === '/';
  const visibleAccountNavigation = accountNavigation.filter((item) => !item.protected || user);
  const unreadMessagesQuery = useUnreadMessageCount(Boolean(user));
  const unreadNotificationsQuery = useUnreadNotificationCount(Boolean(user));
  const unreadCounts = {
    '/messages': user ? unreadMessagesQuery.unreadCount : 0,
    '/notifications': user ? unreadNotificationsQuery.unreadCount || 0 : 0,
  };
  const navigationWithUnread = workspaceNavigation.map((item) => ({
    ...item,
    showUnread: false,
    badge: unreadCounts[item.to] || undefined,
  }));

  const closeMobileMenu = () => setMobileMenuOpen(false);

  return (
    <div className="paper-grid min-h-[100dvh] bg-paper text-ink">
      <div className={`mx-auto min-h-[100dvh] max-w-[1480px] bg-paper-light shadow-rail lg:grid ${isFeed ? 'lg:grid-cols-[228px_minmax(0,1fr)_300px]' : 'lg:grid-cols-[228px_minmax(0,1fr)]'}`}>
        <header className="flex items-center justify-between border-b border-rule bg-paper-light px-5 py-4 lg:hidden">
          <NavLink aria-label="SocialBlog home" className="focus-ring" onClick={closeMobileMenu} to="/">
            <BrandMark compact />
          </NavLink>
          <div className="flex items-center gap-1">
            <IconButton label="Search"><Search aria-hidden="true" className="h-[18px] w-[18px]" strokeWidth={1.8} /></IconButton>
            <IconButton label={mobileMenuOpen ? 'Close menu' : 'Open menu'} onClick={() => setMobileMenuOpen((open) => !open)}>
              {mobileMenuOpen ? <X aria-hidden="true" className="h-[19px] w-[19px]" strokeWidth={1.8} /> : <Menu aria-hidden="true" className="h-[19px] w-[19px]" strokeWidth={1.8} />}
            </IconButton>
          </div>
        </header>

        {mobileMenuOpen && (
          <div className="border-b border-rule bg-paper px-5 py-5 lg:hidden">
            <p className="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">Workspace</p>
            <nav aria-label="Mobile primary navigation" className="space-y-1">
              {navigationWithUnread.map((item) => <NavItem item={item} key={item.to} onNavigate={closeMobileMenu} />)}
            </nav>
            <div className="my-5 h-px bg-rule" />
            <p className="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">Your corner</p>
            <nav aria-label="Mobile account navigation" className="space-y-1">
              {visibleAccountNavigation.map((item) => <NavItem item={item} key={item.to} onNavigate={closeMobileMenu} />)}
              {user ? (
                <button className="focus-ring flex min-h-11 w-full items-center rounded-xl px-3.5 text-left text-sm font-medium text-muted hover:bg-paper-light hover:text-ink" onClick={logout} type="button">Log out</button>
              ) : (
                <NavLink className="focus-ring flex min-h-11 items-center rounded-xl px-3.5 text-sm font-medium text-muted hover:bg-paper-light hover:text-ink" onClick={closeMobileMenu} to="/login">Sign in</NavLink>
              )}
            </nav>
          </div>
        )}

        <aside className="hidden border-r border-rule bg-paper px-5 py-7 lg:flex lg:min-h-[100dvh] lg:flex-col lg:justify-between">
          <div>
            <NavLink aria-label="SocialBlog home" className="focus-ring mb-12 flex" to="/"><BrandMark /></NavLink>
            <p className="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">Workspace</p>
            <nav aria-label="Primary navigation" className="space-y-1">
              {navigationWithUnread.map((item) => <NavItem item={item} key={item.to} />)}
            </nav>
            <div className="my-7 h-px bg-rule" />
            <p className="mb-3 px-3 text-[10px] font-bold uppercase tracking-[.18em] text-muted">Your corner</p>
            <nav aria-label="Account navigation" className="space-y-1">
              {visibleAccountNavigation.map((item) => <NavItem item={item} key={item.to} />)}
              {!user && <NavItem item={{ label: 'Sign in', to: '/login', icon: LogIn }} />}
            </nav>
          </div>
          <div className="border-t border-rule pt-5">
            {user ? (
              <div className="flex items-center gap-3 rounded-xl p-2">
                <Avatar name={user.name} />
                <NavLink className="focus-ring min-w-0 flex-1" to="/profile">
                  <span className="block truncate text-sm font-semibold text-ink">{user.name}</span>
                  <span className="block truncate text-xs text-muted">Member profile</span>
                </NavLink>
                <button aria-label="Log out" className="focus-ring flex min-h-11 min-w-11 items-center justify-center rounded-full text-muted hover:bg-paper-light hover:text-ink" onClick={logout} type="button"><LogOut aria-hidden="true" className="h-4 w-4" /></button>
              </div>
            ) : (
              <div className="rounded-xl bg-paper-light p-3">
                <p className="text-xs font-semibold text-ink">Join the network</p>
                <p className="mt-1 text-xs leading-5 text-muted">Create an account to publish, follow, and connect.</p>
                <NavLink className="focus-ring mt-3 inline-flex min-h-11 items-center text-xs font-semibold text-teal" to="/register">Create account <span aria-hidden="true" className="ml-1">→</span></NavLink>
              </div>
            )}
          </div>
        </aside>

        <main className="min-w-0 bg-paper-light px-5 py-6 sm:px-8 lg:px-10 lg:py-8">
          <Outlet />
        </main>

        {isFeed && <UtilityRail />}
      </div>
    </div>
  );
}
