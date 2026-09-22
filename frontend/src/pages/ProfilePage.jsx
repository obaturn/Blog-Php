import { MapPin, ExternalLink, BriefcaseBusiness } from 'lucide-react';
import { useAuth } from '../context/auth-context';
import { useProfessionalProfile } from '../hooks/useProfessionalProfile';
import Avatar from '../components/Avatar';
import Button from '../components/Button';
import PageHeader from '../components/PageHeader';
import SkillPill from '../components/SkillPill';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

export default function ProfilePage() {
  const { user } = useAuth();
  const profileQuery = useProfessionalProfile(user?.id);
  const profile = profileQuery.data;

  return (
    <div className="mx-auto max-w-[960px]">
        <PageHeader action={<Button to="/profile/edit" variant="outline">Edit profile</Button>} description="A place for the work, context, and connections you want to make easier to find." eyebrow="Your corner" title="Profile" />
      <Surface className="p-6 sm:p-8">
        <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
          <Avatar name={user?.name} size="lg" />
          <div className="min-w-0"><h2 className="font-display text-2xl font-semibold tracking-[-.04em] text-ink">{user?.name || 'Your profile'}</h2><p className="mt-1 text-sm text-muted">{user?.email}</p>{profile?.headline && <p className="mt-4 max-w-[55ch] text-[15px] leading-7 text-[#4f5b55]">{profile.headline}</p>}{profile?.location && <p className="mt-3 flex items-center gap-2 text-xs font-semibold text-muted"><MapPin aria-hidden="true" className="h-4 w-4 text-teal" /> {profile.location}</p>}</div>
        </div>
        {profileQuery.isLoading && <div className="mt-8 space-y-3"><Skeleton className="h-4 w-full" /><Skeleton className="h-4 w-4/5" /></div>}
        {profileQuery.error && <p className="mt-6 text-sm text-clay" role="alert">Professional profile details are not available right now.</p>}
      </Surface>

      {profile && (
        <div className="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(240px,.6fr)]">
          <div className="space-y-6">
            {profile.summary && <Surface className="p-6"><h2 className="font-display text-xl font-semibold tracking-[-.03em] text-ink">About the work</h2><p className="mt-4 whitespace-pre-line text-[15px] leading-7 text-[#4f5b55]">{profile.summary}</p></Surface>}
            {profile.entries?.length > 0 && <Surface className="p-6"><h2 className="font-display text-xl font-semibold tracking-[-.03em] text-ink">Experience and projects</h2><div className="mt-5 divide-y divide-rule">{profile.entries.map((entry) => <div className="py-4 first:pt-0 last:pb-0" key={entry.id}><p className="font-semibold text-ink">{entry.title}</p><p className="mt-1 text-sm text-muted">{entry.organization || entry.type}</p>{entry.description && <p className="mt-2 text-sm leading-6 text-muted">{entry.description}</p>}</div>)}</div></Surface>}
          </div>
          <aside className="space-y-6">
            {profile.skills?.length > 0 && <Surface className="p-6"><h2 className="font-display text-lg font-semibold tracking-[-.03em] text-ink">Skills</h2><div className="mt-4 flex flex-wrap gap-2">{profile.skills.map((skill) => <SkillPill key={skill}>{skill}</SkillPill>)}</div></Surface>}
            <Surface className="p-6"><h2 className="font-display text-lg font-semibold tracking-[-.03em] text-ink">Availability</h2><p className="mt-2 text-sm leading-6 text-muted">{profile.availability === 'open_to_work' ? 'Open to work' : profile.availability === 'freelance' ? 'Available for freelance work' : 'Not currently looking'}</p>{(profile.website_url || profile.github_url || profile.linkedin_url) && <div className="mt-4 space-y-2">{[profile.website_url, profile.github_url, profile.linkedin_url].filter(Boolean).map((url) => <a className="focus-ring flex min-h-11 items-center gap-2 text-sm font-semibold text-teal hover:underline" href={url} key={url} rel="noreferrer" target="_blank"><ExternalLink aria-hidden="true" className="h-4 w-4" /> {new URL(url).hostname.replace('www.', '')}</a>)}</div>}</Surface>
            <div className="flex items-start gap-3 border-l-2 border-teal-soft pl-3 text-xs leading-5 text-muted"><BriefcaseBusiness aria-hidden="true" className="mt-0.5 h-4 w-4 shrink-0 text-teal" />Keep your profile specific enough that the right people know why to reach out.</div>
          </aside>
        </div>
      )}

      {!profileQuery.isLoading && !profileQuery.error && !profile && <div className="mt-6"><StatusPanel actionLabel="Complete your professional profile" title="Your professional profile is still taking shape." message="Add a headline, summary, skills, and a little context about the work you want people to remember." tone="accent" /></div>}
    </div>
  );
}
