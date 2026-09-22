import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useAuth } from '../context/auth-context';
import { getApiErrorMessage } from '../lib/apiErrors';
import { useConversationMessages, useConversations, useStartConversation } from '../hooks/useMessaging';
import Button from '../components/Button';
import ConversationList, { ConversationListSkeleton } from '../components/ConversationList';
import ConversationThread, { ConversationThreadEmpty } from '../components/ConversationThread';
import Field from '../components/Field';
import PageHeader from '../components/PageHeader';
import PaginationControls from '../components/PaginationControls';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

export default function MessagesPage() {
  const { user } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const [startOpen, setStartOpen] = useState(false);
  const [recipientId, setRecipientId] = useState('');
  const [startError, setStartError] = useState('');
  const [page, setPage] = useState(1);
  const conversationsQuery = useConversations({ page });
  const startMutation = useStartConversation();
  const conversations = conversationsQuery.data?.conversations || [];
  const selectedConversationId = searchParams.get('conversation');
  const selectedConversation = conversations.find((conversation) => String(conversation.id) === String(selectedConversationId));
  const activeConversation = selectedConversation || conversations[0];
  const activeConversationId = activeConversation?.id;
  const messagesQuery = useConversationMessages(activeConversationId);

  useEffect(() => {
    if (activeConversationId && String(activeConversationId) !== String(selectedConversationId)) {
      setSearchParams({ conversation: String(activeConversationId) }, { replace: true });
    }
  }, [activeConversationId, selectedConversationId, setSearchParams]);

  const selectConversation = (conversationId) => setSearchParams({ conversation: String(conversationId) });

  const startConversation = (event) => {
    event.preventDefault();
    setStartError('');
    if (!recipientId || Number(recipientId) <= 0) {
      setStartError('Enter a valid user ID to start a direct conversation.');
      return;
    }

    startMutation.mutate(recipientId, {
      onSuccess: (conversation) => {
        setRecipientId('');
        setStartOpen(false);
        setSearchParams({ conversation: String(conversation.id) });
      },
      onError: (error) => setStartError(getApiErrorMessage(error, 'We could not start that conversation.')),
    });
  };

  return (
    <div className="mx-auto max-w-[1080px]">
      <PageHeader action={<Button onClick={() => setStartOpen((open) => !open)} variant="outline">{startOpen ? 'Close' : 'New conversation'}</Button>} description="Keep direct conversations close without losing the wider network." eyebrow="Workspace" title="Messages" />

      {startOpen && <Surface className="mb-6 p-5 sm:p-6"><div className="mb-4"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Start a direct conversation</p><p className="mt-2 text-sm leading-6 text-muted">The current API starts conversations by recipient user ID. You can find the ID from the person’s profile data.</p></div><form aria-busy={startMutation.isPending} className="flex flex-col gap-3 sm:flex-row sm:items-end" onSubmit={startConversation}><div className="w-full sm:max-w-xs"><Field id="recipient-user-id" label="Recipient user ID" min="1" onChange={(event) => setRecipientId(event.target.value)} placeholder="e.g. 12" required type="number" value={recipientId} /></div><Button loading={startMutation.isPending} type="submit">Start conversation</Button></form>{(startError || startMutation.error) && <p className="mt-3 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{startError || getApiErrorMessage(startMutation.error, 'We could not start that conversation.')}</p>}</Surface>}

      {conversationsQuery.error && !conversationsQuery.isLoading ? <StatusPanel actionLabel="Try loading messages again" onAction={() => conversationsQuery.refetch()} title="Unable to load conversations." message="Your inbox could not be reached just now. Try again without losing your place." tone="error" /> : <Surface className="overflow-hidden p-0"><div className="grid lg:grid-cols-[300px_minmax(0,1fr)]">{conversationsQuery.isLoading ? <ConversationListSkeleton /> : <div className={selectedConversationId ? 'hidden lg:block' : 'block'}><ConversationList conversations={conversations} onSelect={selectConversation} onStart={() => setStartOpen(true)} selectedConversationId={selectedConversationId} userId={user?.id} /></div>}{selectedConversation ? <div className={selectedConversationId ? 'block' : 'hidden lg:block'}><ConversationThread conversation={selectedConversation} messages={messagesQuery.data?.messages || []} messagesError={messagesQuery.error} messagesLoading={messagesQuery.isLoading} onBack={() => setSearchParams({})} onRetry={() => messagesQuery.refetch()} userId={user?.id} /></div> : <div className="hidden lg:block"><ConversationThreadEmpty onStart={() => setStartOpen(true)} /></div>}</div>{conversationsQuery.data?.pagination && <div className="px-5 pb-4"><PaginationControls currentPage={conversationsQuery.data.pagination.current_page} label={`${conversationsQuery.data.pagination.total || conversations.length} conversations`} lastPage={conversationsQuery.data.pagination.last_page} loading={conversationsQuery.isFetching} onNext={() => setPage((current) => current + 1)} onPrevious={() => setPage((current) => current - 1)} /></div>}</Surface>}
    </div>
  );
}
