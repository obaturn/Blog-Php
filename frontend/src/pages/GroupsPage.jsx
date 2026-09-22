import { useState } from 'react';
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../context/auth-context';
import Button from '../components/Button';
import Field from '../components/Field';
import PageHeader from '../components/PageHeader';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

async function fetchGroupsPage({ search, pageParam }) {
  const response = await api.get('/api/v1/groups', {
    params: { per_page: 12, ...(search ? { search } : {}), ...(pageParam ? { page: pageParam } : {}) },
  });
  return response.data.data;
}

function GroupSkeleton() {
  return (
    <div aria-hidden="true" className="border border-rule bg-surface p-5">
      <Skeleton className="h-5 w-2/3" />
      <Skeleton className="mt-3 h-3 w-1/3" />
      <Skeleton className="mt-6 h-4 w-full" />
      <Skeleton className="mt-2 h-4 w-4/5" />
    </div>
  );
}

export default function GroupsPage() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [form, setForm] = useState({ name: '', description: '', is_private: false });
  const [formError, setFormError] = useState('');
  const [membershipError, setMembershipError] = useState('');
  const groupsQuery = useInfiniteQuery({
    queryKey: ['groups', search],
    queryFn: ({ pageParam }) => fetchGroupsPage({ search, pageParam }),
    initialPageParam: null,
    getNextPageParam: (lastPage) => (
      lastPage?.pagination?.current_page < lastPage?.pagination?.last_page
        ? lastPage.pagination.current_page + 1
        : undefined
    ),
    retry: 1,
    refetchOnWindowFocus: false,
  });
  const groups = groupsQuery.data?.pages?.flatMap((page) => page.groups || []) || [];

  const createMutation = useMutation({
    mutationFn: (payload) => api.post('/api/v1/groups', payload),
    onSuccess: () => {
      setForm({ name: '', description: '', is_private: false });
      setFormError('');
      queryClient.invalidateQueries({ queryKey: ['groups'] });
    },
    onError: (error) => setFormError(error.response?.data?.message || 'Unable to create this community yet.'),
  });

  const membershipMutation = useMutation({
    mutationFn: ({ groupId, isMember }) => (
      isMember ? api.delete(`/api/v1/groups/${groupId}/leave`) : api.post(`/api/v1/groups/${groupId}/join`)
    ),
    onSuccess: () => {
      setMembershipError('');
      queryClient.invalidateQueries({ queryKey: ['groups'] });
    },
    onError: (error) => setMembershipError(error.response?.data?.message || 'We could not update your membership.'),
  });

  const submit = (event) => {
    event.preventDefault();
    setFormError('');
    createMutation.mutate(form);
  };

  return (
    <div className="mx-auto max-w-[960px]">
      <PageHeader
        action={user ? <Button to="#start-community" variant="outline">Start a community</Button> : null}
        description="Find focused communities, share useful work, and build conversations around what matters to you."
        eyebrow="Community"
        title="Find your people."
      />

      <section className="mb-8 flex flex-col gap-3 border-b border-rule pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div className="max-w-[34ch]"><h2 className="font-display text-xl font-semibold tracking-[-.03em] text-ink">Browse communities</h2><p className="mt-1 text-sm leading-6 text-muted">Search by a name or a question you want to spend more time with.</p></div>
        <div className="w-full sm:max-w-xs"><Field id="group-search" label="Search communities" onChange={(event) => setSearch(event.target.value)} placeholder="Try product, makers, mentoring…" type="search" value={search} /></div>
      </section>

      {user && (
        <Surface className="mb-8 p-5 sm:p-6" id="start-community">
          <div className="mb-5"><p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Make a place for the next conversation</p><h2 className="mt-2 font-display text-xl font-semibold tracking-[-.03em] text-ink">Start a community</h2></div>
          <form aria-busy={createMutation.isPending} className="space-y-5" onSubmit={submit}>
            <div className="grid gap-5 md:grid-cols-2">
              <Field id="group-name" label="Community name" onChange={(event) => setForm({ ...form, name: event.target.value })} placeholder="e.g. Independent makers" required value={form.name} />
              <Field as="textarea" className="min-h-[104px]" id="group-description" label="What is it about?" onChange={(event) => setForm({ ...form, description: event.target.value })} placeholder="Give people a reason to join." value={form.description} />
            </div>
            <label className="flex min-h-11 items-center gap-3 text-sm text-muted" htmlFor="group-private">
              <input checked={form.is_private} className="h-4 w-4 accent-teal" id="group-private" onChange={(event) => setForm({ ...form, is_private: event.target.checked })} type="checkbox" />
              <span>Make this community private</span>
            </label>
            {formError && <p className="text-sm text-clay" role="alert">{formError}</p>}
            <Button loading={createMutation.isPending} type="submit">Create community</Button>
          </form>
        </Surface>
      )}

      {membershipError && <p className="mb-5 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{membershipError}</p>}
      {groupsQuery.isLoading && <div className="grid gap-4 md:grid-cols-2"><GroupSkeleton /><GroupSkeleton /><GroupSkeleton /><GroupSkeleton /></div>}
      {groupsQuery.error && !groupsQuery.isLoading && <StatusPanel actionLabel="Try loading communities again" onAction={() => groupsQuery.refetch()} title="Unable to load communities." message="The directory could not be reached just now. Try again without losing your search." tone="error" />}
      {!groupsQuery.isLoading && !groupsQuery.error && !groups.length && <StatusPanel actionLabel={search ? 'Clear search' : user ? 'Start a community' : 'Return to Feed'} actionTo={search ? undefined : user ? '#start-community' : '/'} onAction={search ? () => setSearch('') : undefined} title={search ? 'No communities match that search.' : 'No communities yet.'} message={search ? 'Try a broader phrase or clear the search to see every community.' : 'The first useful gathering can start here.'} tone="accent" />}

      {!groupsQuery.isLoading && !groupsQuery.error && groups.length > 0 && (
        <div className="grid gap-4 md:grid-cols-2">
          {groups.map((group) => (
            <article className="border border-rule bg-surface p-5 transition-colors hover:border-teal/50" key={group.id}>
              <div className="flex items-start justify-between gap-4">
                <div className="min-w-0"><h2 className="truncate font-display text-xl font-semibold tracking-[-.03em] text-ink">{group.name}</h2><p className="mt-1 text-xs text-muted">{group.members_count || 0} members · Hosted by {group.owner?.name || 'the community'}</p></div>
                {group.is_private && <span className="shrink-0 rounded-full bg-clay-soft px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.12em] text-[#92543d]">Private</span>}
              </div>
              <p className="mt-5 line-clamp-3 text-sm leading-6 text-muted">{group.description || 'A new community waiting for its first conversation.'}</p>
              <div className="mt-6 flex flex-wrap items-center gap-3">
                <Link className="focus-ring inline-flex min-h-11 items-center font-semibold text-teal" to={`/groups/${group.id}`}>View community <span aria-hidden="true" className="ml-1">→</span></Link>
                {user && !group.is_private && <Button loading={membershipMutation.isPending && membershipMutation.variables?.groupId === group.id} onClick={() => membershipMutation.mutate({ groupId: group.id, isMember: group.is_member })} size="sm" variant="outline">{group.is_member ? 'Leave' : 'Join'}</Button>}
              </div>
            </article>
          ))}
        </div>
      )}

      {groupsQuery.hasNextPage && <div className="mt-6 text-center"><Button loading={groupsQuery.isFetchingNextPage} onClick={() => groupsQuery.fetchNextPage()} variant="outline">Load more communities</Button></div>}
    </div>
  );
}
