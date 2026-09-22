import { MessageCircle } from 'lucide-react';
import { formatRelativeTime } from '../lib/formatters';
import Avatar from './Avatar';
import Button from './Button';
import Skeleton from './Skeleton';

function getOtherParticipant(conversation, userId) {
  return conversation.participants?.find((participant) => String(participant.id) !== String(userId)) || conversation.participants?.[0];
}

function getLatestMessage(conversation) {
  return conversation.messages?.[0] || conversation.messages?.at?.(-1);
}

export default function ConversationList({ conversations, selectedConversationId, userId, onSelect, onStart }) {
  return (
    <section aria-labelledby="conversation-list-title" className="min-w-0 border-b border-rule lg:border-b-0 lg:border-r">
      <div className="flex items-center justify-between gap-3 border-b border-rule px-4 py-4 sm:px-5">
        <div><p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Inbox</p><h2 className="mt-1 font-display text-lg font-semibold tracking-[-.03em] text-ink" id="conversation-list-title">Conversations</h2></div>
        <Button aria-label="Start a conversation" onClick={onStart} size="sm" variant="outline"><span aria-hidden="true">+</span><span className="sr-only sm:not-sr-only">New</span></Button>
      </div>
      <div className="max-h-[460px] overflow-y-auto lg:max-h-[600px]">
        {conversations.length ? conversations.map((conversation) => {
          const other = getOtherParticipant(conversation, userId);
          const latest = getLatestMessage(conversation);
          const isSelected = String(conversation.id) === String(selectedConversationId);
          const unread = Number(conversation.unread_messages_count || 0);
          return (
            <button aria-current={isSelected ? 'true' : undefined} className={`focus-ring flex min-h-[84px] w-full items-start gap-3 border-b border-rule px-4 py-4 text-left transition-colors sm:px-5 ${isSelected ? 'bg-teal-soft/70' : 'hover:bg-paper'}`} key={conversation.id} onClick={() => onSelect(conversation.id)} type="button">
              <Avatar name={other?.name} size="lg" tone={isSelected ? 'teal' : 'sand'} />
              <span className="min-w-0 flex-1">
                <span className="flex items-center justify-between gap-3"><span className={`truncate text-sm ${unread ? 'font-bold text-ink' : 'font-semibold text-ink'}`}>{other?.name || 'Direct conversation'}</span><span className="shrink-0 text-[10px] text-muted">{latest?.created_at ? formatRelativeTime(latest.created_at) : ''}</span></span>
                <span className={`mt-1 block truncate text-xs leading-5 ${unread ? 'font-semibold text-ink' : 'text-muted'}`}>{latest?.body || 'Start the conversation.'}</span>
                {unread > 0 && <span className="mt-2 inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[.12em] text-teal"><span aria-hidden="true" className="h-1.5 w-1.5 rounded-full bg-clay" />{unread} unread</span>}
              </span>
            </button>
          );
        }) : (
          <div className="flex flex-col items-start gap-3 px-5 py-8"><MessageCircle aria-hidden="true" className="h-5 w-5 text-teal" /><p className="text-sm font-semibold text-ink">No conversations yet.</p><p className="text-xs leading-5 text-muted">Start a direct conversation when you have a person in mind.</p><Button onClick={onStart} size="sm">Start one</Button></div>
        )}
      </div>
    </section>
  );
}

export function ConversationListSkeleton() {
  return <div aria-busy="true" aria-label="Loading conversations" className="space-y-1 p-4" role="status">{[1, 2, 3].map((item) => <div className="flex items-center gap-3 py-3" key={item}><Skeleton className="h-11 w-11 rounded-full" /><div className="flex-1 space-y-2"><Skeleton className="h-3 w-2/3" /><Skeleton className="h-3 w-full" /></div></div>)}</div>;
}
