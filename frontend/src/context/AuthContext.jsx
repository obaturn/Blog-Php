import { useEffect, useMemo, useState } from 'react';
import api from '../lib/api';
import { AuthContext } from './auth-context';

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('socialblog_token'));
  const [loading, setLoading] = useState(Boolean(token));

  useEffect(() => {
    if (!token) {
      delete api.defaults.headers.common.Authorization;
      return;
    }

    api.defaults.headers.common.Authorization = `Bearer ${token}`;

    api.get('/api/v1/profile')
      .then((response) => {
        setUser(response.data.data.user);
      })
      .catch(() => {
        localStorage.removeItem('socialblog_token');
        setToken(null);
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, [token]);

  const login = async (credentials) => {
    const response = await api.post('/api/v1/login', credentials);
    const authToken = response.data.data.token;
    localStorage.setItem('socialblog_token', authToken);
    setToken(authToken);
    setUser(response.data.data.user);
    return response.data;
  };

  const register = async (payload) => {
    const response = await api.post('/api/v1/register', payload);
    const authToken = response.data.data.token;
    localStorage.setItem('socialblog_token', authToken);
    setToken(authToken);
    setUser(response.data.data.user);
    return response.data;
  };

  const logout = async () => {
    try {
      await api.post('/api/v1/logout');
    } catch (error) {
      console.warn('Logout request failed', error);
    } finally {
      localStorage.removeItem('socialblog_token');
      delete api.defaults.headers.common.Authorization;
      setToken(null);
      setUser(null);
    }
  };

  const value = useMemo(
    () => ({ user, token, loading, login, register, logout, setUser }),
    [user, token, loading],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
