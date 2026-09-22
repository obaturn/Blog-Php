import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../lib/api';

export const notificationQueryKeys = {
  all: ['notifications'],
  list: (page, perPage) => ['notifications', 'list', page, perPage],
  unreadCount: ['notifications', 'unread-count'],
};

export function useNotifications({ page = 1, perPage = 20, enabled = true } = {}) {
  return useQuery({
    queryKey: notificationQueryKeys.list(page, perPage),
    queryFn: async () => {
      const response = await api.get('/api/v1/notifications', { params: { page, per_page: perPage } });
      return response.data.data;
    },
    enabled,
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useUnreadNotificationCount(enabled = true) {
  return useQuery({
    queryKey: notificationQueryKeys.unreadCount,
    queryFn: async () => {
      const response = await api.get('/api/v1/notifications/unread-count');
      return response.data.data.unread_count || 0;
    },
    enabled,
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useMarkNotificationRead() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (notificationId) => {
      const response = await api.post(`/api/v1/notifications/${notificationId}/read`);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: notificationQueryKeys.all });
    },
  });
}

export function useMarkAllNotificationsRead() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      const response = await api.post('/api/v1/notifications/read-all');
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: notificationQueryKeys.all });
    },
  });
}
