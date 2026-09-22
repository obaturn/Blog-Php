import {
  Bell,
  Bookmark,
  BriefcaseBusiness,
  CalendarDays,
  CircleUserRound,
  Layers3,
  MessagesSquare,
  UsersRound,
} from 'lucide-react';

export const workspaceNavigation = [
  { label: 'Feed', to: '/', icon: Layers3 },
  { label: 'Communities', to: '/groups', icon: UsersRound },
  { label: 'Events', to: '/events', icon: CalendarDays },
  { label: 'Jobs', to: '/jobs', icon: BriefcaseBusiness },
  { label: 'Messages', to: '/messages', icon: MessagesSquare, showUnread: true },
  { label: 'Notifications', to: '/notifications', icon: Bell },
];

export const accountNavigation = [
  { label: 'Profile', to: '/profile', icon: CircleUserRound, protected: true },
  { label: 'Saved ideas', to: '/saved', icon: Bookmark, protected: true },
];
