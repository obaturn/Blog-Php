import { useState } from 'react';
import { useCreateJob } from '../hooks/useJobs';
import { getApiErrorMessage, getApiFieldErrors } from '../lib/apiErrors';
import Button from './Button';
import Field from './Field';
import Surface from './Surface';

const initialForm = {
  title: '',
  company_name: '',
  description: '',
  location: '',
  workplace_type: 'remote',
  employment_type: 'full_time',
  salary_min: '',
  salary_max: '',
  currency: 'USD',
  application_url: '',
  expires_at: '',
};

const employmentTypes = [
  ['full_time', 'Full time'],
  ['part_time', 'Part time'],
  ['contract', 'Contract'],
  ['internship', 'Internship'],
  ['freelance', 'Freelance'],
];

function firstError(errors, key) {
  return errors?.[key]?.[0];
}

export default function JobForm({ onCreated }) {
  const [form, setForm] = useState(initialForm);
  const [clientError, setClientError] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const createMutation = useCreateJob();

  const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

  const submit = (event) => {
    event.preventDefault();
    setClientError('');
    setFieldErrors({});

    if (!form.title.trim() || !form.company_name.trim() || !form.description.trim()) {
      return setClientError('Add a title, company, and clear description before publishing.');
    }
    if (form.salary_min && form.salary_max && Number(form.salary_max) < Number(form.salary_min)) {
      return setClientError('The salary maximum needs to be greater than or equal to the minimum.');
    }

    const payload = {
      title: form.title.trim(),
      company_name: form.company_name.trim(),
      description: form.description.trim(),
      location: form.location.trim() || undefined,
      workplace_type: form.workplace_type,
      employment_type: form.employment_type,
      salary_min: form.salary_min ? Number(form.salary_min) : undefined,
      salary_max: form.salary_max ? Number(form.salary_max) : undefined,
      currency: form.currency.trim().toUpperCase() || 'USD',
      application_url: form.application_url.trim() || undefined,
      expires_at: form.expires_at || undefined,
    };

    createMutation.mutate(payload, {
      onSuccess: (data) => onCreated(data.job),
      onError: (error) => {
        setClientError(getApiErrorMessage(error, 'Unable to publish this role yet.'));
        setFieldErrors(getApiFieldErrors(error));
      },
    });
  };

  return (
    <Surface className="p-5 sm:p-7">
      <form aria-busy={createMutation.isPending} className="space-y-5" onSubmit={submit}>
        <div className="grid gap-5 md:grid-cols-2">
          <Field error={firstError(fieldErrors, 'title')} id="job-title" label="Role title" onChange={(event) => update('title', event.target.value)} placeholder="e.g. Product designer" required value={form.title} />
          <Field error={firstError(fieldErrors, 'company_name')} id="job-company" label="Company or team" onChange={(event) => update('company_name', event.target.value)} placeholder="Who is hiring?" required value={form.company_name} />
        </div>
        <Field as="textarea" error={firstError(fieldErrors, 'description')} id="job-description" label="Description" onChange={(event) => update('description', event.target.value)} placeholder="Describe the work, context, and what a good first few months look like." required value={form.description} />
        <div className="grid gap-5 md:grid-cols-2">
          <Field error={firstError(fieldErrors, 'location')} id="job-location" label="Location" onChange={(event) => update('location', event.target.value)} placeholder="City, country, or time zone" value={form.location} />
          <Field as="select" error={firstError(fieldErrors, 'workplace_type')} id="job-workplace" label="Workplace type" onChange={(event) => update('workplace_type', event.target.value)} value={form.workplace_type}>
            <option value="onsite">On-site</option>
            <option value="hybrid">Hybrid</option>
            <option value="remote">Remote</option>
          </Field>
        </div>
        <div className="grid gap-5 md:grid-cols-2">
          <Field as="select" error={firstError(fieldErrors, 'employment_type')} id="job-employment" label="Employment type" onChange={(event) => update('employment_type', event.target.value)} value={form.employment_type}>
            {employmentTypes.map(([value, label]) => <option key={value} value={value}>{label}</option>)}
          </Field>
          <Field error={firstError(fieldErrors, 'expires_at')} hint="Optional. Leave blank for an open-ended listing." id="job-expires" label="Expires on" onChange={(event) => update('expires_at', event.target.value)} type="date" value={form.expires_at} />
        </div>
        <div className="grid gap-5 sm:grid-cols-[1fr_1fr_120px]">
          <Field error={firstError(fieldErrors, 'salary_min')} id="job-salary-min" label="Salary minimum" min="0" onChange={(event) => update('salary_min', event.target.value)} placeholder="Optional" type="number" value={form.salary_min} />
          <Field error={firstError(fieldErrors, 'salary_max')} id="job-salary-max" label="Salary maximum" min="0" onChange={(event) => update('salary_max', event.target.value)} placeholder="Optional" type="number" value={form.salary_max} />
          <Field error={firstError(fieldErrors, 'currency')} id="job-currency" label="Currency" maxLength="3" onChange={(event) => update('currency', event.target.value)} value={form.currency} />
        </div>
        <Field error={firstError(fieldErrors, 'application_url')} hint="Optional. The internal SocialBlog application form remains available too." id="job-application-url" label="External application URL" onChange={(event) => update('application_url', event.target.value)} placeholder="https://…" type="url" value={form.application_url} />
        {(clientError || createMutation.error) && <p className="border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{clientError || getApiErrorMessage(createMutation.error, 'Unable to publish this role yet.')}</p>}
        <div className="flex flex-wrap items-center justify-end gap-3 border-t border-rule pt-5">
          <Button to="/jobs" variant="outline">Cancel</Button>
          <Button loading={createMutation.isPending} type="submit">Publish job</Button>
        </div>
      </form>
    </Surface>
  );
}
