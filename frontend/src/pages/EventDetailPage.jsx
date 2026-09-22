import { CalendarDays, ExternalLink, MapPin, UsersRound } from 'lucide-react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useAttendEvent, useEvent, useLeaveEvent } from '../hooks/useEvents';
import { getApiErrorMessage } from '../lib/apiErrors';
import { useAuth } from '../context/auth-context';
import { formatEventCapacity, formatEventDateRange, formatDateLabel } from '../lib/formatters';
import Avatar from '../components/Avatar';
import Button from '../components/Button';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

export default function EventDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const navigate = useNavigate();
  const eventQuery = useEvent(id);
  const attendMutation = useAttendEvent();
  const leaveMutation = useLeaveEvent();
  const event = eventQuery.data;
  const isHost = Boolean(event && user && (String(event.host_id) === String(user.id) || String(event.host?.id) === String(user.id)));
  const isFull = Boolean(event && event.max_attendees !== null && event.max_attendees !== undefined && Number(event.attendees_count || 0) >= Number(event.max_attendees) && !event.is_attending);
  const isPending = attendMutation.isPending || leaveMutation.isPending;
  const actionError = attendMutation.error || leaveMutation.error;

  if (eventQuery.isLoading) return <div aria-busy="true" className="mx-auto max-w-3xl space-y-4"><Skeleton className="h-4 w-32" /><Skeleton className="h-12 w-4/5" /><Skeleton className="h-5 w-1/2" /><Skeleton className="h-48 w-full" /></div>;
  if (eventQuery.error || !event) return <div className="mx-auto max-w-3xl"><StatusPanel actionLabel="Back to events" actionTo="/events" title="Unable to load this event." message="The event may have moved or the network is unavailable." tone="error" /></div>;

  const attend = () => attendMutation.mutate(event.id);
  const leave = () => leaveMutation.mutate(event.id);

  return (
    <div className="mx-auto max-w-3xl">
      <Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal" to="/events">← Back to events</Link>
      <div className="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_260px]">
        <main>
          <Surface className="p-6 sm:p-8">
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">{event.group?.name || 'Open gathering'}</p>
            <h1 className="mt-3 font-display text-3xl font-semibold leading-tight tracking-[-.05em] text-ink sm:text-4xl">{event.title}</h1>
            <p className="mt-3 text-sm text-muted">Hosted by {event.host?.name || 'a SocialBlog member'} · Published {formatDateLabel(event.created_at)}</p>
            <p className="mt-8 whitespace-pre-line text-[15px] leading-7 text-[#4f5b55]">{event.description || 'This gathering has not added a description yet.'}</p>
            {actionError && <p className="mt-6 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{getApiErrorMessage(actionError, 'Unable to update attendance yet.')}</p>}
            <div className="mt-8 flex flex-wrap gap-3">
              {isHost ? <span className="inline-flex min-h-11 items-center rounded-full bg-teal-soft px-4 text-sm font-semibold text-teal">You&apos;re hosting this event</span> : event.is_attending ? <Button loading={isPending} onClick={leave} variant="outline">Leave event</Button> : isFull ? <Button disabled variant="outline">Event full</Button> : user ? <Button loading={isPending} onClick={attend}>Attend event</Button> : <Button onClick={() => navigate('/login')} variant="outline">Sign in to attend</Button>}
            </div>
          </Surface>
          {event.meeting_url && <Surface className="mt-6 flex items-center justify-between gap-4 p-5"><div><p className="text-xs font-semibold text-ink">Join online</p><p className="mt-1 text-sm text-muted">The host shared a meeting link for attendees.</p></div><a className="focus-ring inline-flex min-h-11 items-center gap-2 rounded-full bg-teal px-4 text-sm font-semibold text-white hover:bg-[#0b5d56]" href={event.meeting_url} rel="noreferrer" target="_blank">Open link <ExternalLink aria-hidden="true" className="h-4 w-4" /></a></Surface>}
        </main>
        <aside className="space-y-4">
          <Surface className="p-5">
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Event details</p>
            <div className="mt-5 space-y-4 text-sm">
              <div className="flex items-start gap-3"><CalendarDays aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" /><div><p className="font-semibold text-ink">When</p><p className="mt-1 leading-6 text-muted">{formatEventDateRange(event.starts_at, event.ends_at)}</p></div></div>
              <div className="flex items-start gap-3"><MapPin aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" /><div><p className="font-semibold text-ink">Where</p><p className="mt-1 leading-6 text-muted">{event.location || 'Location shared with attendees'}</p></div></div>
              <div className="flex items-start gap-3"><UsersRound aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" /><div><p className="font-semibold text-ink">Attendance</p><p className="mt-1 leading-6 text-muted">{formatEventCapacity(event.attendees_count, event.max_attendees)}</p></div></div>
            </div>
          </Surface>
          <Surface className="p-5"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Hosted by</p><div className="mt-4 flex items-center gap-3"><Avatar name={event.host?.name} size="lg" tone="sand" /><div><p className="font-semibold text-ink">{event.host?.name || 'SocialBlog member'}</p><p className="mt-1 text-xs text-muted">Making room for a useful conversation.</p></div></div></Surface>
        </aside>
      </div>
    </div>
  );
}
