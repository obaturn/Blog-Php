import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import api from '../lib/api';

export const jobQueryKeys = {
  all: ['jobs'],
  list: (filters) => ['jobs', 'list', filters],
  detail: (jobId) => ['jobs', 'detail', jobId],
};

async function fetchJobs({ search, location, employmentType, page, perPage }) {
  const response = await api.get('/api/v1/jobs', {
    params: {
      per_page: perPage,
      page,
      ...(search ? { search } : {}),
      ...(location ? { location } : {}),
      ...(employmentType ? { employment_type: employmentType } : {}),
    },
  });

  return response.data.data;
}

async function fetchJob(jobId) {
  const response = await api.get(`/api/v1/jobs/${jobId}`);
  return response.data.data.job;
}

export function useJobs({ search = '', location = '', employmentType = '', page = 1, perPage = 12 }) {
  return useQuery({
    queryKey: jobQueryKeys.list({ search, location, employmentType, page, perPage }),
    queryFn: () => fetchJobs({ search, location, employmentType, page, perPage }),
    placeholderData: (previousData) => previousData,
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useJob(jobId) {
  return useQuery({
    queryKey: jobQueryKeys.detail(jobId),
    queryFn: () => fetchJob(jobId),
    enabled: Boolean(jobId),
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

export function useCreateJob() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload) => {
      const response = await api.post('/api/v1/jobs', payload);
      return response.data.data;
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: jobQueryKeys.all }),
  });
}

export function useSaveJob() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (jobId) => {
      const response = await api.post(`/api/v1/jobs/${jobId}/save`);
      return response.data;
    },
    onSuccess: (_data, jobId) => {
      queryClient.invalidateQueries({ queryKey: jobQueryKeys.all });
      queryClient.invalidateQueries({ queryKey: jobQueryKeys.detail(jobId) });
    },
  });
}

export function useUnsaveJob() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (jobId) => {
      const response = await api.delete(`/api/v1/jobs/${jobId}/save`);
      return response.data;
    },
    onSuccess: (_data, jobId) => {
      queryClient.invalidateQueries({ queryKey: jobQueryKeys.all });
      queryClient.invalidateQueries({ queryKey: jobQueryKeys.detail(jobId) });
    },
  });
}

export function useApplyToJob() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ jobId, payload }) => {
      const response = await api.post(`/api/v1/jobs/${jobId}/apply`, payload);
      return response.data.data;
    },
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: jobQueryKeys.all });
      queryClient.invalidateQueries({ queryKey: jobQueryKeys.detail(variables.jobId) });
    },
  });
}
