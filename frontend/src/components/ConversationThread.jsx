import { ArrowLeft, MoreHorizontal } from 'lucide-react';
import { Link } from 'react-router-dom';
import { formatRelativeTime } from '../lib/formatters';
import Avatar from './Avatar';
import MessageComposer from './MessageComposer';
import Skeleton from './Skeleton';

function getOtherParticipant(conversation, userId) {
  return conversation?.participants?.find((participant) => String(participant.id) !== String(userId)) || conversation?.participants?.[0];
}

export default function ConversationThread({ conversation, messages, messagesLoading, messagesError, userId, onRetry, onBack }) {
  const other = getOtherParticipant(conversation, userId);

  return (
    <section aria-labelledby="conversation-title" className="flex min-h-[620px] min-w-0 flex-col">
      <header className="flex items-center gap-3 border-b border-rule px-4 py-4 sm:px-5">
        <button aria-label="Back to conversations" className="focus-ring flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-muted hover:bg-paper hover:text-teal lg:hidden" onClick={onBack} type="button"><ArrowLeft aria-hidden="true" className="h-5 w-5" /></button>
        <Avatar name={other?.name} size="lg" tone="teal" />
        <div className="min-w-0 flex-1"><h2 className="truncate font-display text-lg font-semibold tracking-[-.03em] text-ink" id="conversation-title">{other?.name || 'Direct conversation'}</h2><p className="mt-1 text-xs text-muted">Private conversation</p></div>
        <button aria-label="Conversation options" className="focus-ring flex h-11 w-11 items-center justify-center rounded-full text-muted hover:bg-paper hover:text-ink" type="button"><MoreHorizontal aria-hidden="true" className="h-5 w-5" /></button>
      </header>

      <div aria-live="polite" className="flex-1 overflow-y-auto bg-paper px-4 py-6 sm:px-6">
        {messagesLoading ? <div aria-busy="true" className="space-y-5" role="status"><Skeleton className="h-12 w-3/5 rounded-2xl" /><Skeleton className="ml-auto h-16 w-3/5 rounded-2xl" /><Skeleton className="h-10 w-2/5 rounded-2xl" /></div> : messagesError ? <div className="flex min-h-[280px] flex-col items-start justify-center"><p className="font-display text-lg font-semibold text-ink">This conversation could not be opened.</p><p className="mt-2 text-sm leading-6 text-muted">Check your connection or try loading the thread again.</p><button className="focus-ring mt-5 min-h-11 rounded-full border border-rule px-4 text-sm font-semibold text-teal hover:border-teal hover:bg-teal-soft" onClick={onRetry} type="button">Try again</button></div> : messages?.length ? <ol className="space-y-4" aria-label={`Messages with ${other?.name || 'this person'}`}>{messages.map((message) => { const isMine = String(message.sender_id) === String(userId); return <li className={`flex ${isMine ? 'justify-end' : 'justify-start'}`} key={message.id}><div className={`max-w-[85%] sm:max-w-[70%] ${isMine ? 'items-end' : 'items-start'} flex flex-col`}><div className={`rounded-2xl px-4 py-3 text-sm leading-6 ${isMine ? 'rounded-br-sm bg-teal text-white' : 'rounded-bl-sm border border-rule bg-surface text-ink'}`}><p className="whitespace-pre-wrap break-words">{message.body}</p></div><span className="mt-1 px-1 text-[10px] text-muted">{isMine ? 'You' : message.sender?.name || other?.name} · {formatRelativeTime(message.created_at)}</span></div></li>; })}</ol> : <div className="flex min-h-[280px] flex-col items-center justify-center text-center"><p className="font-display text-xl font-semibold tracking-[-.03em] text-ink">Start the conversation.</p><p className="mt-2 max-w-[34ch] text-sm leading-6 text-muted">Keep the first message specific enough to give the other person somewhere useful to begin.</p></div>}
      </div>
      <MessageComposer conversationId={conversation?.id} key={conversation?.id} />
    </section>
  );
}

export function ConversationThreadEmpty({ onStart }) {
  return <section className="flex min-h-[620px] flex-col items-center justify-center bg-paper px-6 text-center"><div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-soft text-teal"><Link aria-label="Messages home" to="/messages"><ArrowLeft aria-hidden="true" className="h-5 w-5" /></Link></div><h2 className="font-display text-2xl font-semibold tracking-[-.04em] text-ink">Keep a good conversation close.</h2><p className="mt-2 max-w-[38ch] text-sm leading-6 text-muted">Select a conversation or start a new one when there is a person and a useful question in mind.</p><button className="focus-ring mt-6 min-h-11 rounded-full bg-teal px-4 text-sm font-semibold text-white hover:bg-[#0b5d56]" onClick={onStart} type="button">Start a conversation</button></section>;
}
