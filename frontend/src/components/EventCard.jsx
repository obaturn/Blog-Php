import { CalendarDays, MapPin, UsersRound } from 'lucide-react';
import { Link } from 'react-router-dom';
import { formatEventCapacity, formatEventDateRange, formatEventDay } from '../lib/formatters';
import Avatar from './Avatar';
import Button from './Button';
import Surface from './Surface';

export default function EventCard({
  event,
  user,
  isAttendancePending = false,
  onAttend,
  onLeave,
  onRequireAuth,
}) {
  const dateParts = formatEventDay(event.starts_at);
  const isHost = Boolean(user && (String(event.host_id) === String(user.id) || String(event.host?.id) === String(user.id)));
  const isFull = event.max_attendees !== null
    && event.max_attendees !== undefined
    && Number(event.attendees_count || 0) >= Number(event.max_attendees)
    && !event.is_attending;

  return (
    <Surface as="article" className="p-4 transition-colors hover:border-teal/50 sm:p-5">
      <div className="grid gap-4 sm:grid-cols-[68px_minmax(0,1fr)] sm:gap-5">
        <div aria-label={`Event date: ${dateParts.month} ${dateParts.day}`} className="flex h-[68px] w-[68px] flex-col items-center justify-center rounded-xl bg-clay-soft text-[#92543d]">
          <CalendarDays aria-hidden="true" className="mb-1 h-4 w-4" strokeWidth={1.8} />
          <span className="text-[10px] font-bold uppercase tracking-[.12em]">{dateParts.month}</span>
          <span className="font-display text-2xl font-semibold leading-none">{dateParts.day}</span>
        </div>

        <div className="min-w-0">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
              <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">{event.group?.name || 'Open gathering'}</p>
              <h2 className="mt-2 font-display text-xl font-semibold leading-tight tracking-[-.035em] text-ink">
                <Link className="focus-ring rounded-sm hover:text-teal" to={`/events/${event.id}`}>{event.title}</Link>
              </h2>
              <p className="mt-2 text-xs leading-5 text-muted">Hosted by {event.host?.name || 'a SocialBlog member'}</p>
            </div>
            <Link className="focus-ring inline-flex min-h-11 shrink-0 items-center text-xs font-semibold text-teal hover:underline" to={`/events/${event.id}`}>
              View event <span aria-hidden="true" className="ml-1">→</span>
            </Link>
          </div>

          <p className="mt-4 line-clamp-2 max-w-[68ch] text-sm leading-6 text-[#4f5b55]">{event.description || 'A useful gathering for people who want to spend time on the same question.'}</p>

          <div className="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-muted">
            <span className="inline-flex items-center gap-1.5"><CalendarDays aria-hidden="true" className="h-3.5 w-3.5 text-teal" />{formatEventDateRange(event.starts_at, event.ends_at)}</span>
            {event.location && <span className="inline-flex items-center gap-1.5"><MapPin aria-hidden="true" className="h-3.5 w-3.5 text-teal" />{event.location}</span>}
            <span className="inline-flex items-center gap-1.5"><UsersRound aria-hidden="true" className="h-3.5 w-3.5 text-teal" />{formatEventCapacity(event.attendees_count, event.max_attendees)}</span>
          </div>

          <div className="mt-5 flex flex-wrap items-center gap-3 border-t border-rule pt-4">
            <Avatar name={event.host?.name} size="sm" tone="sand" />
            <span className="mr-auto text-xs text-muted">{event.is_attending ? 'You are attending' : 'Make room for a useful conversation.'}</span>
            {isHost ? (
              <span className="text-xs font-semibold text-teal">You&apos;re hosting</span>
            ) : event.is_attending ? (
              <Button loading={isAttendancePending} onClick={() => onLeave(event.id)} size="sm" variant="outline">Leave event</Button>
            ) : isFull ? (
              <Button disabled size="sm" variant="outline">Event full</Button>
            ) : user ? (
              <Button loading={isAttendancePending} onClick={() => onAttend(event.id)} size="sm">Attend event</Button>
            ) : (
              <Button onClick={onRequireAuth} size="sm" variant="outline">Sign in to attend</Button>
            )}
          </div>
        </div>
      </div>
    </Surface>
  );
}
