import { useRef, useState } from 'react';
import { Send } from 'lucide-react';
import { useSendMessage } from '../hooks/useMessaging';
import { getApiErrorMessage } from '../lib/apiErrors';
import Button from './Button';
import Field from './Field';

export default function MessageComposer({ conversationId }) {
  const [body, setBody] = useState('');
  const [error, setError] = useState('');
  const textareaRef = useRef(null);
  const sendMutation = useSendMessage();

  const send = (event) => {
    event.preventDefault();
    const trimmedBody = body.trim();
    if (!trimmedBody || sendMutation.isPending) return;

    setError('');
    sendMutation.mutate({ conversationId, body: trimmedBody }, {
      onSuccess: () => {
        setBody('');
        textareaRef.current?.focus();
      },
      onError: (requestError) => setError(getApiErrorMessage(requestError, 'Your message could not be sent.')),
    });
  };

  return (
    <form aria-busy={sendMutation.isPending} className="border-t border-rule bg-paper-light p-4 sm:p-5" onSubmit={send}>
      <Field as="textarea" className="min-h-[84px]" id="message-body" label="Write a message" maxLength={5000} onChange={(event) => setBody(event.target.value)} onKeyDown={(event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); event.currentTarget.form?.requestSubmit(); } }} placeholder="Write something useful…" ref={textareaRef} value={body} />
      <div className="mt-3 flex items-center justify-between gap-3"><p className="text-[11px] text-muted">Enter to send · Shift+Enter for a new line</p><Button disabled={!body.trim()} loading={sendMutation.isPending} size="sm" type="submit">Send <Send aria-hidden="true" className="h-4 w-4" /></Button></div>
      {(error || sendMutation.error) && <p className="mt-3 text-xs text-clay" role="alert">{error || getApiErrorMessage(sendMutation.error, 'Your message could not be sent.')}</p>}
    </form>
  );
}
