import { useState } from 'react';
import { Bookmark, BriefcaseBusiness, ExternalLink, MapPin } from 'lucide-react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useJob, useSaveJob, useUnsaveJob } from '../hooks/useJobs';
import { getApiErrorMessage } from '../lib/apiErrors';
import { useAuth } from '../context/auth-context';
import { formatDateLabel, formatEmploymentType, formatSalaryRange, formatWorkplaceType } from '../lib/formatters';
import ApplyJobForm from '../components/ApplyJobForm';
import Avatar from '../components/Avatar';
import Button from '../components/Button';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

export default function JobDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const navigate = useNavigate();
  const [submitted, setSubmitted] = useState(false);
  const jobQuery = useJob(id);
  const saveMutation = useSaveJob();
  const unsaveMutation = useUnsaveJob();
  const job = jobQuery.data;
  const isOwner = Boolean(job && user && String(job.posted_by) === String(user.id));
  const isExpired = Boolean(job?.expires_at && new Date(job.expires_at).getTime() < new Date().setHours(0, 0, 0, 0));
  const isClosed = job?.status !== 'open' || isExpired;
  const actionError = saveMutation.error || unsaveMutation.error;

  if (jobQuery.isLoading) return <div aria-busy="true" className="mx-auto max-w-3xl space-y-4"><Skeleton className="h-4 w-28" /><Skeleton className="h-12 w-4/5" /><Skeleton className="h-5 w-1/2" /><Skeleton className="h-48 w-full" /></div>;
  if (jobQuery.error || !job) return <div className="mx-auto max-w-3xl"><StatusPanel actionLabel="Back to jobs" actionTo="/jobs" title="Unable to load this role." message="The role may have closed or the network is unavailable." tone="error" /></div>;

  const toggleSave = () => {
    if (!user) {
      navigate('/login');
      return;
    }
    if (job.is_saved) unsaveMutation.mutate(job.id);
    else saveMutation.mutate(job.id);
  };

  return (
    <div className="mx-auto max-w-3xl">
      <Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal" to="/jobs">← Back to jobs</Link>
      <div className="mt-4 grid gap-6 lg:grid-cols-[minmax(0,1fr)_260px]">
        <main className="space-y-6">
          <Surface className="p-6 sm:p-8">
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">{job.company_name}</p>
            <h1 className="mt-3 font-display text-3xl font-semibold leading-tight tracking-[-.05em] text-ink sm:text-4xl">{job.title}</h1>
            <p className="mt-3 text-sm text-muted">Posted {formatDateLabel(job.created_at)} by {job.poster?.name || 'a SocialBlog member'}</p>
            <p className="mt-8 whitespace-pre-line text-[15px] leading-7 text-[#4f5b55]">{job.description}</p>
            {actionError && <p className="mt-6 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{getApiErrorMessage(actionError, 'Unable to update this saved role yet.')}</p>}
            <div className="mt-8 flex flex-wrap gap-3">
              <Button aria-pressed={Boolean(job.is_saved)} loading={saveMutation.isPending || unsaveMutation.isPending} onClick={toggleSave} variant={job.is_saved ? 'primary' : 'outline'}><Bookmark aria-hidden="true" className="h-4 w-4" fill={job.is_saved ? 'currentColor' : 'none'} />{job.is_saved ? 'Saved' : 'Save role'}</Button>
              {job.application_url && <a className="focus-ring inline-flex min-h-11 items-center gap-2 rounded-full border border-rule px-4 text-sm font-semibold text-teal hover:border-teal hover:bg-teal-soft" href={job.application_url} rel="noreferrer" target="_blank">External application <ExternalLink aria-hidden="true" className="h-4 w-4" /></a>}
            </div>
          </Surface>
          {isOwner ? <Surface className="p-5"><p className="text-sm font-semibold text-ink">This is your listing.</p><p className="mt-1 text-sm leading-6 text-muted">Application review tools can be added to your hiring workspace next.</p></Surface> : submitted || job.has_applied ? <Surface className="border-teal/40 bg-teal-soft/50 p-5"><p className="text-sm font-semibold text-teal">Application submitted.</p><p className="mt-1 text-sm leading-6 text-muted">The hiring team has your application. Keep your profile current so the rest of your context is easy to find.</p></Surface> : isClosed ? <Surface className="p-5"><p className="text-sm font-semibold text-ink">This role is no longer accepting applications.</p><p className="mt-1 text-sm leading-6 text-muted">You can still save the listing for reference.</p></Surface> : user ? <ApplyJobForm jobId={job.id} onSubmitted={() => setSubmitted(true)} /> : <Surface className="flex flex-col items-start gap-3 p-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-sm leading-6 text-muted">Sign in to apply and keep your application history in one place.</p><Button to="/login" variant="outline">Sign in to apply</Button></Surface>}
        </main>
        <aside className="space-y-4">
          <Surface className="p-5">
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Role details</p>
            <div className="mt-5 space-y-4 text-sm">
              <div className="flex items-start gap-3"><BriefcaseBusiness aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" /><div><p className="font-semibold text-ink">Workplace</p><p className="mt-1 leading-6 text-muted">{formatWorkplaceType(job.workplace_type)}</p></div></div>
              <div className="flex items-start gap-3"><MapPin aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" /><div><p className="font-semibold text-ink">Location</p><p className="mt-1 leading-6 text-muted">{job.location || 'Location flexible'}</p></div></div>
              <div><p className="font-semibold text-ink">Employment</p><p className="mt-1 leading-6 text-muted">{formatEmploymentType(job.employment_type)}</p></div>
              <div><p className="font-semibold text-ink">Compensation</p><p className="mt-1 leading-6 text-muted">{formatSalaryRange(job.salary_min, job.salary_max, job.currency)}</p></div>
              <div><p className="font-semibold text-ink">Closing date</p><p className="mt-1 leading-6 text-muted">{job.expires_at ? formatDateLabel(job.expires_at) : 'Open until filled'}</p></div>
            </div>
          </Surface>
          <Surface className="p-5"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-muted">Posted by</p><div className="mt-4 flex items-center gap-3"><Avatar name={job.poster?.name} size="lg" tone="teal" /><div><p className="font-semibold text-ink">{job.poster?.name || 'SocialBlog member'}</p><p className="mt-1 text-xs text-muted">Sharing work with the network.</p></div></div></Surface>
        </aside>
      </div>
    </div>
  );
}
