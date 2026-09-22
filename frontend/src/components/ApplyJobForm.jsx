import { useState } from 'react';
import { useApplyToJob } from '../hooks/useJobs';
import { getApiErrorMessage, getApiFieldErrors } from '../lib/apiErrors';
import Button from './Button';
import Field from './Field';
import Surface from './Surface';

export default function ApplyJobForm({ jobId, onSubmitted }) {
  const [form, setForm] = useState({ cover_letter: '', resume_url: '' });
  const [fieldErrors, setFieldErrors] = useState({});
  const applyMutation = useApplyToJob();

  const submit = (event) => {
    event.preventDefault();
    setFieldErrors({});
    applyMutation.mutate({
      jobId,
      payload: {
        cover_letter: form.cover_letter.trim(),
        resume_url: form.resume_url.trim() || undefined,
      },
    }, {
      onSuccess: () => onSubmitted(),
      onError: (error) => setFieldErrors(getApiFieldErrors(error)),
    });
  };

  return (
    <Surface className="p-5 sm:p-6">
      <div className="mb-5">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Take the next step</p>
        <h2 className="mt-2 font-display text-xl font-semibold tracking-[-.03em] text-ink">Apply for this role</h2>
        <p className="mt-2 text-sm leading-6 text-muted">Share enough context for the hiring team to understand why this work fits you.</p>
      </div>
      <form aria-busy={applyMutation.isPending} className="space-y-5" onSubmit={submit}>
        <Field as="textarea" error={fieldErrors.cover_letter?.[0]} id="application-cover-letter" label="Cover letter" onChange={(event) => setForm((current) => ({ ...current, cover_letter: event.target.value }))} placeholder="What would you bring to this role?" required value={form.cover_letter} />
        <Field error={fieldErrors.resume_url?.[0]} hint="Optional. Add a public document or portfolio URL." id="application-resume-url" label="Resume or portfolio URL" onChange={(event) => setForm((current) => ({ ...current, resume_url: event.target.value }))} placeholder="https://…" type="url" value={form.resume_url} />
        {applyMutation.error && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{getApiErrorMessage(applyMutation.error, 'Unable to send your application yet.')}</p>}
        <div className="flex justify-end">
          <Button disabled={!form.cover_letter.trim()} loading={applyMutation.isPending} type="submit">Send application</Button>
        </div>
      </form>
    </Surface>
  );
}
