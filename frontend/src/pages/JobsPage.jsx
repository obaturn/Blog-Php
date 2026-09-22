import { useDeferredValue, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useJobs, useSaveJob, useUnsaveJob } from '../hooks/useJobs';
import { getApiErrorMessage } from '../lib/apiErrors';
import { useAuth } from '../context/auth-context';
import Button from '../components/Button';
import Field from '../components/Field';
import JobCard from '../components/JobCard';
import PageHeader from '../components/PageHeader';
import PaginationControls from '../components/PaginationControls';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

function JobSkeleton() {
  return <div aria-hidden="true" className="border border-rule bg-surface p-5 sm:p-6"><div className="flex gap-3"><Skeleton className="h-11 w-11 rounded-full" /><div className="flex-1 space-y-3"><Skeleton className="h-3 w-28" /><Skeleton className="h-6 w-3/4" /><Skeleton className="h-4 w-full" /><Skeleton className="h-4 w-2/3" /></div></div><Skeleton className="mt-6 h-4 w-1/3" /></div>;
}

export default function JobsPage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [search, setSearch] = useState('');
  const [location, setLocation] = useState('');
  const [employmentType, setEmploymentType] = useState('');
  const [page, setPage] = useState(1);
  const [actionError, setActionError] = useState('');
  const deferredSearch = useDeferredValue(search);
  const deferredLocation = useDeferredValue(location);
  const jobsQuery = useJobs({ search: deferredSearch, location: deferredLocation, employmentType, page });
  const saveMutation = useSaveJob();
  const unsaveMutation = useUnsaveJob();
  const jobs = jobsQuery.data?.jobs || [];
  const pagination = jobsQuery.data?.pagination;
  const isSavePending = (jobId) => (saveMutation.isPending && saveMutation.variables === jobId) || (unsaveMutation.isPending && unsaveMutation.variables === jobId);

  const handleSave = (jobId) => {
    setActionError('');
    saveMutation.mutate(jobId, { onError: (error) => setActionError(getApiErrorMessage(error, 'Unable to save this role yet.')) });
  };

  const handleUnsave = (jobId) => {
    setActionError('');
    unsaveMutation.mutate(jobId, { onError: (error) => setActionError(getApiErrorMessage(error, 'Unable to remove this saved role yet.')) });
  };

  const clearFilters = () => {
    setSearch('');
    setLocation('');
    setEmploymentType('');
    setPage(1);
  };

  return (
    <div className="mx-auto max-w-[960px]">
      <PageHeader action={user ? <Button to="/jobs/create">Post a job</Button> : <Button to="/login" variant="outline">Sign in to post</Button>} description="Find work that fits the people, practices, and communities you are building with." eyebrow="Workspace" title="Jobs" />

      <Surface className="mb-7 p-4 sm:p-5">
        <div className="mb-4"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Open roles</p><p className="mt-1 text-sm text-muted">Search by role, place, or the kind of work you want to make room for.</p></div>
        <div className="grid gap-4 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_180px]">
          <Field id="job-search" label="Search jobs" onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Role, company, or craft…" type="search" value={search} />
          <Field id="job-location-filter" label="Location" onChange={(event) => { setLocation(event.target.value); setPage(1); }} placeholder="Remote, Lagos, London…" value={location} />
          <Field as="select" id="job-employment-filter" label="Employment type" onChange={(event) => { setEmploymentType(event.target.value); setPage(1); }} value={employmentType}>
            <option value="">All types</option><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="internship">Internship</option><option value="freelance">Freelance</option>
          </Field>
        </div>
      </Surface>

      {actionError && <p className="mb-5 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{actionError}</p>}
      {jobsQuery.isLoading && <div aria-busy="true" className="space-y-4"><JobSkeleton /><JobSkeleton /><JobSkeleton /></div>}
      {jobsQuery.error && !jobsQuery.isLoading && <StatusPanel actionLabel="Try loading jobs again" onAction={() => jobsQuery.refetch()} title="Unable to load jobs." message="The roles directory could not be reached just now. Try again without losing your filters." tone="error" />}
      {!jobsQuery.isLoading && !jobsQuery.error && !jobs.length && <StatusPanel actionLabel={search || location || employmentType ? 'Clear filters' : user ? 'Post a job' : 'Return to Feed'} actionTo={!search && !location && !employmentType && user ? '/jobs/create' : !search && !location && !employmentType ? '/' : undefined} onAction={search || location || employmentType ? clearFilters : undefined} title={search || location || employmentType ? 'No open roles match those filters.' : 'No open roles yet.'} message={search || location || employmentType ? 'Try clearing a filter or broadening your search.' : 'Share the first opportunity that would help this network grow.'} tone="accent" />}
      {!jobsQuery.isLoading && !jobsQuery.error && jobs.length > 0 && (
        <div className="space-y-4">
          {jobs.map((job) => <JobCard isSavePending={isSavePending(job.id)} job={job} key={job.id} onRequireAuth={() => navigate('/login')} onSave={handleSave} onUnsave={handleUnsave} user={user} />)}
          <PaginationControls currentPage={pagination?.current_page} label={`${pagination?.total || jobs.length} roles`} lastPage={pagination?.last_page} loading={jobsQuery.isFetching} onNext={() => setPage((current) => current + 1)} onPrevious={() => setPage((current) => current - 1)} />
        </div>
      )}
    </div>
  );
}
