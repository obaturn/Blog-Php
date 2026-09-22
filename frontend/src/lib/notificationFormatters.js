import {
  BriefcaseBusiness,
  CalendarDays,
  CheckCircle2,
  Heart,
  MessageCircle,
  Sparkles,
  UserPlus,
  UsersRound,
} from 'lucide-react';

const notificationCopy = {
  new_follower: { label: 'New follower', Icon: UserPlus, href: (data) => data.user_id ? '/profile' : null },
  post_liked: { label: 'Post reaction', Icon: Heart, href: (data) => data.post_id ? `/posts/${data.post_id}` : null },
  post_commented: { label: 'Post comment', Icon: MessageCircle, href: (data) => data.post_id ? `/posts/${data.post_id}#comments` : null },
  event_attendee_joined: { label: 'Event update', Icon: CalendarDays, href: (data) => data.event_id ? `/events/${data.event_id}` : null },
  group_member_joined: { label: 'Community update', Icon: UsersRound, href: (data) => data.group_id ? `/groups/${data.group_id}` : null },
  job_application_status_changed: { label: 'Application update', Icon: BriefcaseBusiness, href: (data) => data.job_id ? `/jobs/${data.job_id}` : null },
  connection_requested: { label: 'Connection request', Icon: UserPlus, href: () => '/profile' },
  connection_accepted: { label: 'Connection accepted', Icon: CheckCircle2, href: () => '/profile' },
  recommendation_received: { label: 'Professional recommendation', Icon: Sparkles, href: () => '/profile' },
  mentorship_requested: { label: 'Mentorship request', Icon: UsersRound, href: () => '/profile' },
  mentorship_request_status_changed: { label: 'Mentorship update', Icon: UsersRound, href: () => '/profile' },
};

function parseData(value) {
  if (!value) return {};
  if (typeof value === 'object') return value;
  try {
    return JSON.parse(value);
  } catch {
    return {};
  }
}

export function formatNotification(notification) {
  const payload = parseData(notification?.data);
  const type = payload.type || notification?.type?.split('\\').pop() || 'activity';
  const metadata = parseData(payload.data);
  const config = notificationCopy[type] || { label: 'Network activity', Icon: Sparkles, href: () => null };

  return {
    type,
    label: config.label,
    Icon: config.Icon,
    message: payload.message || 'There is new activity waiting for you.',
    href: config.href(metadata),
    metadata,
  };
}
