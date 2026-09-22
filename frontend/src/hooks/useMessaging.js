import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../lib/api';

export const messagingQueryKeys = {
  all: ['messaging'],
  conversations: (page, perPage) => ['messaging', 'conversations', page, perPage],
  messages: (conversationId, page, perPage) => ['messaging', 'messages', conversationId, page, perPage],
};

export function useConversations({ page = 1, perPage = 20, enabled = true } = {}) {
  return useQuery({
    queryKey: messagingQueryKeys.conversations(page, perPage),
    queryFn: async () => {
      const response = await api.get('/api/v1/conversations', { params: { page, per_page: perPage } });
      return response.data.data;
    },
    enabled,
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useUnreadMessageCount(enabled = true) {
  const query = useConversations({ page: 1, perPage: 50, enabled });
  const conversations = query.data?.conversations || [];
  const unreadCount = conversations.reduce((total, conversation) => total + Number(conversation.unread_messages_count || 0), 0);

  return { ...query, unreadCount };
}

export function useConversationMessages(conversationId, { page = 1, perPage = 100, enabled = true } = {}) {
  return useQuery({
    queryKey: messagingQueryKeys.messages(conversationId, page, perPage),
    queryFn: async () => {
      const response = await api.get(`/api/v1/conversations/${conversationId}/messages`, { params: { page, per_page: perPage } });
      return response.data.data;
    },
    enabled: Boolean(conversationId) && enabled,
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useStartConversation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (userId) => {
      const response = await api.post('/api/v1/conversations', { user_id: Number(userId) });
      return response.data.data.conversation;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: messagingQueryKeys.all }),
  });
}

export function useSendMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ conversationId, body }) => {
      const response = await api.post(`/api/v1/conversations/${conversationId}/messages`, { body });
      return response.data.data.message;
    },
    onSuccess: (_message, variables) => {
      queryClient.invalidateQueries({ queryKey: messagingQueryKeys.all });
      queryClient.invalidateQueries({ queryKey: ['messaging', 'messages', variables.conversationId] });
    },
  });
}
