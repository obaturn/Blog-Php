import { Bookmark, BriefcaseBusiness, MapPin } from 'lucide-react';
import { Link } from 'react-router-dom';
import { formatEmploymentType, formatSalaryRange, formatWorkplaceType } from '../lib/formatters';
import Avatar from './Avatar';
import Button from './Button';
import Surface from './Surface';

export default function JobCard({
  job,
  user,
  isSavePending = false,
  onSave,
  onUnsave,
  onRequireAuth,
}) {
  return (
    <Surface as="article" className="p-5 transition-colors hover:border-teal/50 sm:p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div className="flex min-w-0 items-start gap-3">
          <Avatar name={job.company_name || job.poster?.name} size="lg" tone="teal" />
          <div className="min-w-0">
            <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">{job.company_name}</p>
            <h2 className="mt-2 font-display text-xl font-semibold leading-tight tracking-[-.035em] text-ink">
              <Link className="focus-ring rounded-sm hover:text-teal" to={`/jobs/${job.id}`}>{job.title}</Link>
            </h2>
            <p className="mt-2 text-xs text-muted">Posted by {job.poster?.name || 'a SocialBlog member'}</p>
          </div>
        </div>
        <Link className="focus-ring inline-flex min-h-11 shrink-0 items-center text-xs font-semibold text-teal hover:underline" to={`/jobs/${job.id}`}>
          View role <span aria-hidden="true" className="ml-1">→</span>
        </Link>
      </div>

      <p className="mt-5 line-clamp-2 max-w-[72ch] text-sm leading-6 text-[#4f5b55]">{job.description}</p>

      <div className="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-xs text-muted">
        <span className="inline-flex items-center gap-1.5"><BriefcaseBusiness aria-hidden="true" className="h-3.5 w-3.5 text-teal" />{formatWorkplaceType(job.workplace_type)}</span>
        <span className="font-semibold text-ink">{formatEmploymentType(job.employment_type)}</span>
        {job.location && <span className="inline-flex items-center gap-1.5"><MapPin aria-hidden="true" className="h-3.5 w-3.5 text-teal" />{job.location}</span>}
      </div>

      <div className="mt-5 flex flex-wrap items-center gap-3 border-t border-rule pt-4">
        <span className="text-xs font-semibold text-ink">{formatSalaryRange(job.salary_min, job.salary_max, job.currency)}</span>
        <span className="mr-auto text-xs text-muted">{job.expires_at ? `Closes ${new Date(job.expires_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}` : 'Open until filled'}</span>
        {user ? (
          <Button
            aria-pressed={Boolean(job.is_saved)}
            loading={isSavePending}
            onClick={() => (job.is_saved ? onUnsave(job.id) : onSave(job.id))}
            size="sm"
            variant={job.is_saved ? 'primary' : 'outline'}
          >
            <Bookmark aria-hidden="true" className="h-4 w-4" fill={job.is_saved ? 'currentColor' : 'none'} />
            {job.is_saved ? 'Saved' : 'Save role'}
          </Button>
        ) : (
          <Button onClick={onRequireAuth} size="sm" variant="outline"><Bookmark aria-hidden="true" className="h-4 w-4" />Save role</Button>
        )}
      </div>
    </Surface>
  );
}
