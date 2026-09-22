import { useQuery } from '@tanstack/react-query';
import { Bell, Compass, Search, Shapes, Sparkles, UsersRound } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../context/auth-context';
import { useUnreadNotificationCount } from '../hooks/useNotifications';
import { formatDateLabel } from '../lib/formatters';
import Avatar from './Avatar';
import IconButton from './IconButton';
import { Skeleton } from './Skeleton';

async function fetchUtilityGroups() {
  const response = await api.get('/api/v1/groups', { params: { per_page: 3 } });
  return response.data.data.groups || [];
}

async function fetchUtilityEvents() {
  const response = await api.get('/api/v1/events', { params: { per_page: 1 } });
  return response.data.data.events || [];
}

async function fetchConnections() {
  const response = await api.get('/api/v1/connections');
  return response.data.data.connections || [];
}

function SectionHeader({ title, action, to, id }) {
  return (
    <div className="mb-4 flex items-center justify-between gap-3">
      <h2 className="font-display text-[17px] font-semibold tracking-[-.03em] text-ink" id={id}>{title}</h2>
      {to ? <Link className="focus-ring text-[11px] font-semibold text-teal" to={to}>{action}</Link> : <span className="text-[11px] font-semibold text-teal">{action}</span>}
    </div>
  );
}

function RailLoading() {
  return (
    <div aria-hidden="true" className="space-y-4">
      <div className="flex items-center gap-3">
        <Skeleton className="h-9 w-9 rounded-full" />
        <div className="flex-1 space-y-2"><Skeleton className="h-3 w-24" /><Skeleton className="h-3 w-16" /></div>
      </div>
      <div className="flex items-center gap-3">
        <Skeleton className="h-9 w-9 rounded-full" />
        <div className="flex-1 space-y-2"><Skeleton className="h-3 w-28" /><Skeleton className="h-3 w-20" /></div>
      </div>
    </div>
  );
}

function ConnectionRow({ connection, user }) {
  const other = connection.requester?.id === user?.id ? connection.recipient : connection.requester;
  if (!other) return null;

  return (
    <div className="flex items-center gap-3">
      <Avatar name={other.name} size="sm" tone="sand" />
      <div className="min-w-0 flex-1">
        <p className="truncate text-xs font-semibold text-ink">{other.name}</p>
        <p className="truncate text-[11px] text-muted">Professional connection</p>
      </div>
      <span className="text-[11px] font-semibold text-teal">Connected</span>
    </div>
  );
}

export default function UtilityRail() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const unreadNotificationsQuery = useUnreadNotificationCount(Boolean(user));
  const groupsQuery = useQuery({ queryKey: ['utility-groups'], queryFn: fetchUtilityGroups });
  const eventsQuery = useQuery({ queryKey: ['utility-events'], queryFn: fetchUtilityEvents });
  const connectionsQuery = useQuery({
    queryKey: ['connections'],
    queryFn: fetchConnections,
    enabled: Boolean(user),
  });

  const event = eventsQuery.data?.[0];
  const groups = groupsQuery.data || [];
  const connections = connectionsQuery.data || [];

  return (
    <aside className="border-t border-rule bg-paper px-5 py-7 sm:px-8 lg:border-l lg:border-t-0 lg:px-6 lg:py-8">
      <div className="mb-8 flex items-center justify-between">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Stay close</p>
        <div className="flex items-center gap-1">
          <IconButton label="Search"><Search aria-hidden="true" className="h-[17px] w-[17px]" strokeWidth={1.8} /></IconButton>
          <IconButton className="relative" label={unreadNotificationsQuery.data ? `${unreadNotificationsQuery.data} unread notifications` : 'Notifications'} onClick={() => navigate('/notifications')}>
            {unreadNotificationsQuery.data > 0 && <span className="absolute right-2.5 top-2.5 h-1.5 w-1.5 rounded-full bg-clay" />}
            <span className="sr-only">{unreadNotificationsQuery.data > 0 ? `${unreadNotificationsQuery.data} unread notifications` : 'No unread notifications'}</span>
            <Bell aria-hidden="true" className="h-[17px] w-[17px]" strokeWidth={1.8} />
          </IconButton>
        </div>
      </div>

      <section aria-labelledby="people-title" className="mb-8">
        <SectionHeader action={connections.length ? 'See all' : 'Build your circle'} id="people-title" title="People to meet" to="/profile" />
        {connectionsQuery.isLoading ? <RailLoading /> : connections.length ? (
          <div className="space-y-4">{connections.slice(0, 3).map((connection) => <ConnectionRow connection={connection} key={connection.id} user={user} />)}</div>
        ) : (
          <div className="border-l-2 border-teal-soft pl-3 text-xs leading-5 text-muted">Connect with people from their professional profiles to make this space more useful.</div>
        )}
      </section>

      <section aria-labelledby="upcoming-title" className="mb-8 border-y border-rule py-6">
        <SectionHeader action="All events" id="upcoming-title" title="Upcoming" to="/events" />
        {eventsQuery.isLoading ? <RailLoading /> : event ? (
          <div className="flex gap-3">
            <div className="flex h-[58px] w-[52px] shrink-0 flex-col items-center justify-center rounded-[10px] bg-clay-soft text-[#92543d]">
              <span className="text-[10px] font-bold uppercase tracking-[.12em]">{new Date(event.starts_at).toLocaleDateString(undefined, { month: 'short' })}</span>
              <span className="font-display text-[22px] font-semibold leading-none">{new Date(event.starts_at).getDate()}</span>
            </div>
            <div>
              <p className="text-[13px] font-semibold leading-5 text-ink">{event.title}</p>
              <p className="mt-1 text-[11px] leading-5 text-muted">{formatDateLabel(event.starts_at)}{event.location ? ` · ${event.location}` : ''}</p>
              <Link className="focus-ring mt-2 inline-flex min-h-11 items-center text-[11px] font-semibold text-teal hover:underline" to={`/events/${event.id}`}>View event <span aria-hidden="true" className="ml-1">→</span></Link>
            </div>
          </div>
        ) : (
          <p className="text-xs leading-5 text-muted">No upcoming events yet. Create a gathering when your community is ready.</p>
        )}
      </section>

      <section aria-labelledby="communities-title">
        <SectionHeader action="Browse all" id="communities-title" title="Communities to explore" to="/groups" />
        {groupsQuery.isLoading ? <RailLoading /> : groups.length ? (
          <div className="space-y-4">
            {groups.map((group, index) => {
              const Icon = index === 0 ? Compass : index === 1 ? Shapes : Sparkles;
              return (
                <Link className="focus-ring group -mx-2 flex items-start gap-3 rounded-[10px] p-2 hover:bg-surface" key={group.id} to={`/groups/${group.id}`}>
                  <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] ${index === 2 ? 'bg-clay-soft text-[#92543d]' : index === 1 ? 'bg-[#e7e4d8] text-[#69705e]' : 'bg-teal-soft text-teal'}`}>
                    <Icon aria-hidden="true" className="h-[17px] w-[17px]" strokeWidth={1.8} />
                  </span>
                  <span className="min-w-0">
                    <span className="block truncate text-xs font-semibold text-ink">{group.name}</span>
                    <span className="mt-1 block text-[11px] text-muted">{group.members_count || 0} members</span>
                  </span>
                </Link>
              );
            })}
          </div>
        ) : (
          <div className="flex items-start gap-3 border-l-2 border-teal-soft pl-3 text-xs leading-5 text-muted">
            <UsersRound aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" />
            <span>No communities yet. Start one around a useful conversation.</span>
          </div>
        )}
      </section>

      <div className="mt-10 border-t border-rule pt-5 text-[11px] leading-5 text-muted">
        <span className="font-semibold text-ink">Quiet by design.</span> Keep the important conversations close and the rest easy to find.
      </div>
    </aside>
  );
}
