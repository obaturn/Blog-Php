import { useInfiniteQuery } from '@tanstack/react-query';
import api from '../lib/api';

export const feedQueryKey = (mode, userId) => ['feed', mode, userId || 'guest'];

async function fetchFeedPage({ mode, pageParam }) {
  const endpoint = mode === 'following' ? '/api/v1/feed' : '/api/v1/feed/public';
  const response = await api.get(endpoint, {
    params: {
      limit: 15,
      ...(pageParam ? { cursor: pageParam } : {}),
    },
  });

  return response.data.data;
}

export function useFeed({ mode, user }) {
  return useInfiniteQuery({
    queryKey: feedQueryKey(mode, user?.id),
    queryFn: ({ pageParam }) => fetchFeedPage({ mode, pageParam }),
    initialPageParam: null,
    enabled: mode === 'explore' || Boolean(user),
    retry: 1,
    refetchOnWindowFocus: false,
    getNextPageParam: (lastPage) => (
      lastPage?.pagination?.has_more ? lastPage.pagination.next_cursor : undefined
    ),
  });
}
