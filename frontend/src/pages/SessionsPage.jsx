import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { X } from 'lucide-react';
import { getApiErrorMessage } from '../lib/apiErrors';
import Button from '../components/Button';
import Surface from '../components/Surface';
import StatusPanel from '../components/StatusPanel';
import api from '../lib/api';

export default function SessionsPage() {
  const navigate = useNavigate();
  const [tokens, setTokens] = useState([]);
  const [loading, setLoading] = useState(false);
  const [revokingId, setRevokingId] = useState(null);
  const [error, setError] = useState('');
  const [revokeAllLoading, setRevokeAllLoading] = useState(false);

  const loadTokens = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/api/v1/tokens');
      setTokens(response.data.data.tokens || []);
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Unable to load sessions.'));
    } finally {
      setLoading(false);
    }
  };

  const revokeToken = async (tokenId) => {
    setRevokingId(tokenId);
    try {
      await api.delete(`/api/v1/tokens/${tokenId}`);
      setTokens((prev) => prev.filter((t) => t.id !== tokenId));
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Unable to revoke this session.'));
    } finally {
      setRevokingId(null);
    }
  };

  const revokeAll = async () => {
    if (!confirm('Sign out from all devices? This will end every active session including this one.')) return;
    setRevokeAllLoading(true);
    try {
      await api.delete('/api/v1/revoke-all');
      setTokens([]);
      navigate('/profile');
    } catch (requestError) {
      setError(getApiErrorMessage(requestError, 'Unable to revoke sessions.'));
    } finally {
      setRevokeAllLoading(false);
    }
  };

  return (
    <div className="mx-auto max-w-[640px]">
      <nav className="mb-6"><Link className="focus-ring inline-flex min-h-11 items-center text-sm font-semibold text-teal hover:underline" to="/profile">← Back to profile</Link></nav>
      <Surface className="p-6 sm:p-8">
        <p className="text-[10px] font-bold uppercase tracking-[.18em] text-teal">Security</p>
        <h1 className="mt-2 font-display text-3xl font-semibold tracking-[-.05em] text-ink">Active sessions.</h1>
        <p className="mt-3 text-sm leading-6 text-muted">Manage the devices where you are signed in. Sign out from any session you no longer recognize.</p>
        {error && <p className="mt-5 border-l-2 border-clay bg-clay-soft px-3 py-2 text-sm text-[#92543d]" role="alert">{error}</p>}

        <div className="mt-6 flex flex-wrap items-center justify-between gap-3">
          <Button onClick={loadTokens} loading={loading} variant="outline">{tokens.length ? 'Refresh sessions' : 'Load sessions'}</Button>
          {tokens.length > 0 && (
            <Button onClick={revokeAll} loading={revokeAllLoading} variant="clay">Sign out everywhere</Button>
          )}
        </div>

        {tokens.length > 0 && (
          <div className="mt-6 divide-y divide-rule border-y border-rule">
            {tokens.map((token) => (
              <div key={token.id} className="flex items-center justify-between gap-4 py-4">
                <div className="min-w-0">
                  <p className="truncate text-sm font-semibold text-ink">{token.name || 'Session'}</p>
                  <p className="mt-1 text-xs text-muted">
                    {token.created_at ? new Date(token.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Unknown date'}
                    {token.last_used_at ? ` · Last used ${new Date(token.last_used_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}` : ''}
                  </p>
                </div>
                <Button size="sm" variant="outline" onClick={() => revokeToken(token.id)} loading={revokingId === token.id}>
                  <X aria-hidden="true" className="h-4 w-4" /> Revoke
                </Button>
              </div>
            ))}
          </div>
        )}

        {!loading && tokens.length === 0 && !error && (
          <StatusPanel actionLabel="Load sessions" onAction={loadTokens} title="No session data loaded." message="Click to see your active sessions." tone="neutral" />
        )}
      </Surface>
    </div>
  );
}
