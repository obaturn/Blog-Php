import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../lib/api';

export const eventQueryKeys = {
  all: ['events'],
  list: (filters) => ['events', 'list', filters],
  detail: (eventId) => ['events', 'detail', eventId],
  groups: ['events', 'form-groups'],
};

async function fetchEvents({ search, page, perPage }) {
  const response = await api.get('/api/v1/events', {
    params: {
      per_page: perPage,
      page,
      ...(search ? { search } : {}),
    },
  });

  return response.data.data;
}

async function fetchEvent(eventId) {
  const response = await api.get(`/api/v1/events/${eventId}`);
  return response.data.data.event;
}

export function useEvents({ search = '', page = 1, perPage = 12 }) {
  return useQuery({
    queryKey: eventQueryKeys.list({ search, page, perPage }),
    queryFn: () => fetchEvents({ search, page, perPage }),
    placeholderData: (previousData) => previousData,
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useEvent(eventId) {
  return useQuery({
    queryKey: eventQueryKeys.detail(eventId),
    queryFn: () => fetchEvent(eventId),
    enabled: Boolean(eventId),
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useEventGroups() {
  return useQuery({
    queryKey: eventQueryKeys.groups,
    queryFn: async () => {
      const response = await api.get('/api/v1/groups', { params: { per_page: 50 } });
      return response.data.data.groups || [];
    },
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useCreateEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload) => {
      const response = await api.post('/api/v1/events', payload);
      return response.data.data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: eventQueryKeys.all }),
  });
}

export function useAttendEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (eventId) => {
      const response = await api.post(`/api/v1/events/${eventId}/attend`);
      return response.data;
    },
    onSuccess: (_data, eventId) => {
      queryClient.invalidateQueries({ queryKey: eventQueryKeys.all });
      queryClient.invalidateQueries({ queryKey: eventQueryKeys.detail(eventId) });
    },
  });
}

export function useLeaveEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (eventId) => {
      const response = await api.delete(`/api/v1/events/${eventId}/leave`);
      return response.data;
    },
    onSuccess: (_data, eventId) => {
      queryClient.invalidateQueries({ queryKey: eventQueryKeys.all });
      queryClient.invalidateQueries({ queryKey: eventQueryKeys.detail(eventId) });
    },
  });
}
