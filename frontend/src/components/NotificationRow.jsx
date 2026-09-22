import { Check, ExternalLink } from 'lucide-react';
import { Link } from 'react-router-dom';
import { formatRelativeTime } from '../lib/formatters';
import { formatNotification } from '../lib/notificationFormatters';
import Button from './Button';
import Surface from './Surface';

export default function NotificationRow({ notification, isPending, onMarkRead }) {
  const { Icon, label, message, href } = formatNotification(notification);
  const isUnread = !notification.read_at;

  return (
    <Surface as="article" className={`p-4 transition-colors sm:p-5 ${isUnread ? 'border-teal/30 bg-teal-soft/20' : ''}`}>
      <div className="flex items-start gap-3 sm:gap-4">
        <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${isUnread ? 'bg-teal-soft text-teal' : 'bg-paper text-muted'}`}><Icon aria-hidden="true" className="h-[18px] w-[18px]" strokeWidth={1.8} /></span>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-x-2 gap-y-1"><p className="text-[10px] font-bold uppercase tracking-[.15em] text-teal">{label}</p>{isUnread && <span className="h-1.5 w-1.5 rounded-full bg-clay" title="Unread" />}<span className="text-[11px] text-muted">{formatRelativeTime(notification.created_at)}</span></div>
          <p className={`mt-2 text-sm leading-6 ${isUnread ? 'font-semibold text-ink' : 'text-[#4f5b55]'}`}>{message}</p>
          <div className="mt-3 flex flex-wrap items-center gap-3">
            {href && <Link className="focus-ring inline-flex min-h-11 items-center gap-1.5 text-xs font-semibold text-teal hover:underline" to={href}>Open context <ExternalLink aria-hidden="true" className="h-3.5 w-3.5" /></Link>}
            {isUnread && <Button loading={isPending} onClick={() => onMarkRead(notification.id)} size="sm" variant="quiet"><Check aria-hidden="true" className="h-4 w-4" />Mark read</Button>}
          </div>
        </div>
      </div>
    </Surface>
  );
}
