import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('socialblog_token');
      delete api.defaults.headers.common.Authorization;
    }

    return Promise.reject(error);
  },
);

const token = localStorage.getItem('socialblog_token');
if (token) {
  api.defaults.headers.common.Authorization = `Bearer ${token}`;
}

export default api;
