import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { LockKeyhole, UsersRound } from 'lucide-react';
import { Link, useParams } from 'react-router-dom';
import api from '../lib/api';
import { useAuth } from '../context/auth-context';
import Button from '../components/Button';
import Skeleton from '../components/Skeleton';
import StatusPanel from '../components/StatusPanel';
import Surface from '../components/Surface';

async function fetchGroup(groupId) {
  const response = await api.get(`/api/v1/groups/${groupId}`);
  return response.data.data.group;
}

export default function GroupDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const groupQuery = useQuery({ queryKey: ['group', id], queryFn: () => fetchGroup(id), retry: 1 });
  const membershipMutation = useMutation({
    mutationFn: ({ isMember }) => (isMember ? api.delete(`/api/v1/groups/${id}/leave`) : api.post(`/api/v1/groups/${id}/join`)),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['group', id] }),
  });

  if (groupQuery.isLoading) return <div aria-busy="true" className="mx-auto max-w-3xl space-y-4"><Skeleton className="h-4 w-28" /><Skeleton className="h-12 w-2/3" /><Skeleton className="h-5 w-1/3" /><Skeleton className="h-32 w-full" /></div>;
  if (groupQuery.error) return <div className="mx-auto max-w-3xl"><StatusPanel actionLabel="Back to communities" actionTo="/groups" title="Unable to load this community." message="The community may have moved or the network is unavailable." tone="error" /></div>;

  const group = groupQuery.data;

  return (
    <div className="mx-auto max-w-3xl">
      <Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal" to="/groups">← Back to communities</Link>
      <Surface className="mt-4 p-6 sm:p-8">
        <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
          <div><div className="flex items-center gap-2 text-xs font-semibold text-muted"><UsersRound aria-hidden="true" className="h-4 w-4 text-teal" /> Community</div><h1 className="mt-4 font-display text-3xl font-semibold tracking-[-.05em] text-ink sm:text-4xl">{group.name}</h1><p className="mt-2 text-sm text-muted">{group.members_count || 0} members · Hosted by {group.owner?.name || 'the community'}</p></div>
          {group.is_private && <span className="inline-flex min-h-8 items-center gap-1 rounded-full bg-clay-soft px-3 text-xs font-semibold text-[#92543d]"><LockKeyhole aria-hidden="true" className="h-3.5 w-3.5" /> Private</span>}
        </div>
        <p className="mt-8 max-w-[65ch] whitespace-pre-line text-[15px] leading-7 text-[#4f5b55]">{group.description || 'This community has not added a description yet.'}</p>
        {user && !group.is_private && <Button className="mt-8" loading={membershipMutation.isPending} onClick={() => membershipMutation.mutate({ isMember: group.is_member })} variant={group.is_member ? 'outline' : 'primary'}>{group.is_member ? 'Leave community' : 'Join community'}</Button>}
        {group.is_private && <p className="mt-8 border-l-2 border-clay-soft pl-3 text-sm leading-6 text-muted">This community is private and requires an invitation from its owner.</p>}
      </Surface>
    </div>
  );
}
