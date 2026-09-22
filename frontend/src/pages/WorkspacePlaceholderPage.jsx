import { ArrowUpRight, BriefcaseBusiness, CalendarDays, Bell, Bookmark, MessagesSquare } from 'lucide-react';
import Button from '../components/Button';
import PageHeader from '../components/PageHeader';
import Surface from '../components/Surface';

const workspaceCopy = {
  events: { title: 'Events', description: 'Keep track of the conversations, gatherings, and working sessions worth showing up for.', icon: CalendarDays },
  jobs: { title: 'Jobs', description: 'A focused place for work that fits the people and communities you are building with.', icon: BriefcaseBusiness },
  messages: { title: 'Messages', description: 'Direct conversations will live here when you are ready to connect one-to-one.', icon: MessagesSquare },
  notifications: { title: 'Notifications', description: 'A quieter view of the reactions, replies, and invitations that need your attention.', icon: Bell },
  saved: { title: 'Saved ideas', description: 'Keep the posts and conversations you want to return to close at hand.', icon: Bookmark },
};

export default function WorkspacePlaceholderPage({ kind = 'events' }) {
  const copy = workspaceCopy[kind] || workspaceCopy.events;
  const Icon = copy.icon;

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader eyebrow="Workspace" title={copy.title} description={copy.description} />
      <Surface className="flex flex-col items-start gap-5 p-6 sm:p-8">
        <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-soft text-teal">
          <Icon aria-hidden="true" className="h-6 w-6" strokeWidth={1.7} />
        </span>
        <div>
          <h2 className="font-display text-xl font-semibold tracking-[-.03em] text-ink">This workspace is ready for its next API-backed slice.</h2>
          <p className="mt-2 max-w-[54ch] text-sm leading-6 text-muted">The navigation is in place without inventing records that the current React routes do not yet fetch.</p>
        </div>
        <Button to="/" variant="outline">Return to Feed <ArrowUpRight aria-hidden="true" className="h-4 w-4" /></Button>
      </Surface>
    </div>
  );
}
