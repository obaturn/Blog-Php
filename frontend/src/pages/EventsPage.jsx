import { useDeferredValue, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAttendEvent, useEvents, useLeaveEvent } from '../hooks/useEvents';
import { getApiErrorMessage } from '../lib/apiErrors';
import { useAuth } from '../context/auth-context';
import Button from '../components/Button';
import EventCard from '../components/EventCard';
import Field from '../components/Field';
import PageHeader from '../components/PageHeader';
import PaginationControls from '../components/PaginationControls';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

function EventSkeleton() {
  return (
    <div aria-hidden="true" className="border border-rule bg-surface p-5">
      <div className="flex gap-5"><Skeleton className="h-[68px] w-[68px] shrink-0 rounded-xl" /><div className="flex-1 space-y-3"><Skeleton className="h-3 w-32" /><Skeleton className="h-6 w-3/4" /><Skeleton className="h-4 w-full" /><Skeleton className="h-4 w-1/2" /></div></div>
    </div>
  );
}

export default function EventsPage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [actionError, setActionError] = useState('');
  const deferredSearch = useDeferredValue(search);
  const eventsQuery = useEvents({ search: deferredSearch, page });
  const attendMutation = useAttendEvent();
  const leaveMutation = useLeaveEvent();
  const events = eventsQuery.data?.events || [];
  const pagination = eventsQuery.data?.pagination;
  const isAttendancePending = (eventId) => (
    (attendMutation.isPending && attendMutation.variables === eventId)
    || (leaveMutation.isPending && leaveMutation.variables === eventId)
  );

  const handleAttend = (eventId) => {
    setActionError('');
    attendMutation.mutate(eventId, { onError: (error) => setActionError(getApiErrorMessage(error, 'Unable to join this event yet.')) });
  };

  const handleLeave = (eventId) => {
    setActionError('');
    leaveMutation.mutate(eventId, { onError: (error) => setActionError(getApiErrorMessage(error, 'Unable to leave this event yet.')) });
  };

  return (
    <div className="mx-auto max-w-[960px]">
      <PageHeader
        action={user ? <Button to="/events/create">Create event</Button> : <Button to="/login" variant="outline">Sign in to host</Button>}
        description="Make room for conversations, gatherings, and working sessions worth showing up for."
        eyebrow="Workspace"
        title="Events"
      />

      <Surface className="mb-7 p-4 sm:p-5">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Upcoming events</p><p className="mt-1 text-sm text-muted">Search the network by what you want to spend time on next.</p></div>
          <div className="w-full sm:max-w-sm"><Field id="event-search" label="Search events" onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Try systems, workshops, or a city…" type="search" value={search} /></div>
        </div>
      </Surface>

      {actionError && <p className="mb-5 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{actionError}</p>}
      {eventsQuery.isLoading && <div aria-busy="true" className="space-y-4"><EventSkeleton /><EventSkeleton /><EventSkeleton /></div>}
      {eventsQuery.error && !eventsQuery.isLoading && <StatusPanel actionLabel="Try loading events again" onAction={() => eventsQuery.refetch()} title="Unable to load events." message="The events directory could not be reached just now. Your place is still here when you are ready to try again." tone="error" />}
      {!eventsQuery.isLoading && !eventsQuery.error && !events.length && <StatusPanel actionLabel={search ? 'Clear search' : user ? 'Create an event' : 'Return to Feed'} actionTo={!search && user ? '/events/create' : !search ? '/' : undefined} onAction={search ? () => { setSearch(''); setPage(1); } : undefined} title={search ? 'No upcoming events match that search.' : 'No upcoming events yet.'} message={search ? 'Try a broader phrase or clear the search to see every upcoming gathering.' : 'Host the first useful gathering for your network.'} tone="accent" />}
      {!eventsQuery.isLoading && !eventsQuery.error && events.length > 0 && (
        <div className="space-y-4">
          {events.map((event) => <EventCard event={event} isAttendancePending={isAttendancePending(event.id)} key={event.id} onAttend={handleAttend} onLeave={handleLeave} onRequireAuth={() => navigate('/login')} user={user} />)}
          <PaginationControls currentPage={pagination?.current_page} label={`${pagination?.total || events.length} events`} lastPage={pagination?.last_page} loading={eventsQuery.isFetching} onNext={() => setPage((current) => current + 1)} onPrevious={() => setPage((current) => current - 1)} />
        </div>
      )}
    </div>
  );
}
