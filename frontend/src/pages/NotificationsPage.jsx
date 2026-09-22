import { useState } from 'react';
import { useAuth } from '../context/auth-context';
import { getApiErrorMessage } from '../lib/apiErrors';
import { useMarkAllNotificationsRead, useMarkNotificationRead, useNotifications } from '../hooks/useNotifications';
import Button from '../components/Button';
import NotificationRow from '../components/NotificationRow';
import PageHeader from '../components/PageHeader';
import PaginationControls from '../components/PaginationControls';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

function NotificationSkeleton() {
  return <div aria-hidden="true" className="border border-rule bg-surface p-5"><div className="flex gap-4"><Skeleton className="h-10 w-10 shrink-0 rounded-xl" /><div className="flex-1 space-y-3"><Skeleton className="h-3 w-32" /><Skeleton className="h-4 w-4/5" /><Skeleton className="h-3 w-20" /></div></div></div>;
}

export default function NotificationsPage() {
  const { user } = useAuth();
  const [page, setPage] = useState(1);
  const [actionError, setActionError] = useState('');
  const notificationsQuery = useNotifications({ page });
  const markReadMutation = useMarkNotificationRead();
  const markAllMutation = useMarkAllNotificationsRead();
  const notifications = notificationsQuery.data?.notifications || [];
  const unreadCount = Number(notificationsQuery.data?.unread_count || 0);
  const pagination = notificationsQuery.data?.pagination;

  const markRead = (notificationId) => {
    setActionError('');
    markReadMutation.mutate(notificationId, { onError: (error) => setActionError(getApiErrorMessage(error, 'That notification could not be marked as read.')) });
  };

  const markAllRead = () => {
    setActionError('');
    markAllMutation.mutate(undefined, { onError: (error) => setActionError(getApiErrorMessage(error, 'Notifications could not be marked as read.')) });
  };

  return (
    <div className="mx-auto max-w-[820px]">
      <PageHeader action={unreadCount > 0 ? <Button loading={markAllMutation.isPending} onClick={markAllRead} variant="outline">Mark all read</Button> : null} description="A quieter view of the reactions, replies, and invitations that need your attention." eyebrow="Workspace" title="Notifications" />

      <Surface className="mb-7 flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"><div><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Your activity</p><p className="mt-2 text-sm leading-6 text-muted">Keep the signal, leave the rest easy to revisit.</p></div><p className="font-display text-2xl font-semibold tracking-[-.04em] text-ink">{unreadCount} <span className="font-body text-sm font-medium tracking-normal text-muted">unread</span></p></Surface>

      {actionError && <p className="mb-5 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{actionError}</p>}
      {notificationsQuery.isLoading && <div aria-busy="true" className="space-y-3"><NotificationSkeleton /><NotificationSkeleton /><NotificationSkeleton /></div>}
      {notificationsQuery.error && !notificationsQuery.isLoading && <StatusPanel actionLabel="Try loading notifications again" onAction={() => notificationsQuery.refetch()} title="Unable to load notifications." message="Your activity feed could not be reached just now. Try again without losing your place." tone="error" />}
      {!notificationsQuery.isLoading && !notificationsQuery.error && !notifications.length && <StatusPanel actionTo="/" title="Nothing needs your attention yet." message="When someone follows, reacts, replies, or invites you into something useful, it will show up here." tone="accent" />}
      {!notificationsQuery.isLoading && !notificationsQuery.error && notifications.length > 0 && <div className="space-y-3">{notifications.map((notification) => <NotificationRow isPending={markReadMutation.isPending && markReadMutation.variables === notification.id} key={notification.id} notification={notification} onMarkRead={markRead} />)}<PaginationControls currentPage={pagination?.current_page} label={`${pagination?.total || notifications.length} notifications`} lastPage={pagination?.last_page} loading={notificationsQuery.isFetching} onNext={() => setPage((current) => current + 1)} onPrevious={() => setPage((current) => current - 1)} /></div>}
      {!user && <p className="mt-6 text-center text-xs text-muted">Sign in to see activity from your network.</p>}
    </div>
  );
}
