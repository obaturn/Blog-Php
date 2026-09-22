import { useState } from 'react';
import { useEventGroups, useCreateEvent } from '../hooks/useEvents';
import { getApiErrorMessage, getApiFieldErrors } from '../lib/apiErrors';
import Button from './Button';
import Field from './Field';
import Surface from './Surface';

const initialForm = {
  title: '',
  description: '',
  starts_at: '',
  ends_at: '',
  location: '',
  meeting_url: '',
  max_attendees: '',
  group_id: '',
};

function firstError(errors, key) {
  return errors?.[key]?.[0];
}

function toApiDate(value) {
  return value ? new Date(value).toISOString() : undefined;
}

export default function EventForm({ onCreated }) {
  const [form, setForm] = useState(initialForm);
  const [clientError, setClientError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const createMutation = useCreateEvent();
  const groupsQuery = useEventGroups();

  const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

  const submit = (event) => {
    event.preventDefault();
    setClientError('');
    setFieldErrors({});

    if (!form.title.trim()) return setClientError('Give the event a clear title before publishing.');
    if (!form.starts_at || !form.ends_at) return setClientError('Choose both a start and end time.');

    const startsAt = new Date(form.starts_at);
    const endsAt = new Date(form.ends_at);
    if (Number.isNaN(startsAt.getTime()) || Number.isNaN(endsAt.getTime())) return setClientError('Use valid start and end times.');
    if (endsAt <= startsAt) return setClientError('The end time needs to come after the start time.');

    const payload = {
      title: form.title.trim(),
      description: form.description.trim() || undefined,
      starts_at: toApiDate(form.starts_at),
      ends_at: toApiDate(form.ends_at),
      location: form.location.trim() || undefined,
      meeting_url: form.meeting_url.trim() || undefined,
      max_attendees: form.max_attendees ? Number(form.max_attendees) : undefined,
      group_id: form.group_id ? Number(form.group_id) : undefined,
    };

    createMutation.mutate(payload, {
      onSuccess: (data) => onCreated(data.event),
      onError: (error) => {
        setClientError(getApiErrorMessage(error, 'Unable to publish this event yet.'));
        setFieldErrors(getApiFieldErrors(error));
      },
    });
  };

  return (
    <Surface className="p-5 sm:p-7">
      <form aria-busy={createMutation.isPending} className="space-y-5" onSubmit={submit}>
        <div className="grid gap-5 md:grid-cols-2">
          <Field error={firstError(fieldErrors, 'title')} id="event-title" label="Title" onChange={(event) => update('title', event.target.value)} placeholder="e.g. Design systems in the open" required value={form.title} />
          <Field error={firstError(fieldErrors, 'location')} id="event-location" label="Location or venue" onChange={(event) => update('location', event.target.value)} placeholder="Online, Accra, or a studio address" value={form.location} />
        </div>
        <Field as="textarea" error={firstError(fieldErrors, 'description')} id="event-description" label="What will people find here?" onChange={(event) => update('description', event.target.value)} placeholder="Give people enough context to decide whether to join." value={form.description} />
        <div className="grid gap-5 md:grid-cols-2">
          <Field error={firstError(fieldErrors, 'starts_at')} id="event-starts-at" label="Starts at" onChange={(event) => update('starts_at', event.target.value)} required type="datetime-local" value={form.starts_at} />
          <Field error={firstError(fieldErrors, 'ends_at')} id="event-ends-at" label="Ends at" onChange={(event) => update('ends_at', event.target.value)} required type="datetime-local" value={form.ends_at} />
        </div>
        <div className="grid gap-5 md:grid-cols-2">
          <Field error={firstError(fieldErrors, 'meeting_url')} hint="Optional. Include a public video or meeting link." id="event-meeting-url" label="Meeting URL" onChange={(event) => update('meeting_url', event.target.value)} placeholder="https://…" type="url" value={form.meeting_url} />
          <Field error={firstError(fieldErrors, 'max_attendees')} hint="Leave blank for an open gathering." id="event-capacity" label="Maximum attendees" min="1" onChange={(event) => update('max_attendees', event.target.value)} placeholder="No limit" type="number" value={form.max_attendees} />
        </div>
        <Field as="select" error={firstError(fieldErrors, 'group_id')} hint={groupsQuery.error ? 'Groups could not be loaded; you can still publish an open event.' : 'Only communities you belong to can host a group event.'} id="event-group" label="Community (optional)" onChange={(event) => update('group_id', event.target.value)} value={form.group_id}>
          <option value="">Open to the wider network</option>
          {(groupsQuery.data || []).filter((group) => group.is_member).map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}
        </Field>
        {(clientError || createMutation.error) && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{clientError || getApiErrorMessage(createMutation.error, 'Unable to publish this event yet.')}</p>}
        <div className="flex flex-wrap items-center justify-end gap-3 border-t border-rule pt-5">
          <Button to="/events" variant="outline">Cancel</Button>
          <Button loading={createMutation.isPending} type="submit">Publish event</Button>
        </div>
      </form>
    </Surface>
  );
}
