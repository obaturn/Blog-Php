import { useQuery } from '@tanstack/react-query';
import api from '../lib/api';

async function fetchProfessionalProfile(userId) {
  try {
    const response = await api.get(`/api/v1/users/${userId}/professional-profile`);
    return response.data.data.profile;
  } catch (error) {
    if (error.response?.status === 404) return null;
    throw error;
  }
}

export function useProfessionalProfile(userId) {
  return useQuery({
    queryKey: ['professional-profile', userId],
    queryFn: () => fetchProfessionalProfile(userId),
    enabled: Boolean(userId),
    retry: false,
  });
}
