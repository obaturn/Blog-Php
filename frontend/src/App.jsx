import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import Layout from './components/Layout';
import StatusPanel from './components/StatusPanel';
import FeedPage from './pages/FeedPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import CreatePostPage from './pages/CreatePostPage';
import PostDetailPage from './pages/PostDetailPage';
import ProfilePage from './pages/ProfilePage';
import GroupsPage from './pages/GroupsPage';
import GroupDetailPage from './pages/GroupDetailPage';
import EventsPage from './pages/EventsPage';
import EventDetailPage from './pages/EventDetailPage';
import CreateEventPage from './pages/CreateEventPage';
import JobsPage from './pages/JobsPage';
import JobDetailPage from './pages/JobDetailPage';
import CreateJobPage from './pages/CreateJobPage';
import MessagesPage from './pages/MessagesPage';
import NotificationsPage from './pages/NotificationsPage';
import WorkspacePlaceholderPage from './pages/WorkspacePlaceholderPage';
import NotFoundPage from './pages/NotFoundPage';
import WelcomePage from './pages/WelcomePage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import EmailVerificationPage from './pages/EmailVerificationPage';
import EditProfilePage from './pages/EditProfilePage';
import ChangePasswordPage from './pages/ChangePasswordPage';
import SessionsPage from './pages/SessionsPage';
import DeleteAccountPage from './pages/DeleteAccountPage';
import { AuthProvider } from './context/AuthContext';
import { useAuth } from './context/auth-context';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="mx-auto max-w-xl"><StatusPanel title="Opening your workspace…" message="Checking your session before we let you in." /></div>;
  }

  if (!user) return <Navigate replace to="/login" />;
  return children;
}

function AuthGuard({ children }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="mx-auto max-w-xl"><StatusPanel title="Opening your workspace…" message="Checking your session before we let you in." /></div>;
  }

  if (!user) return <Navigate replace to="/welcome" />;
  return children;
}

function AppRoutes() {
  return (
    <Routes>
      <Route element={<Layout />} path="/">
        <Route element={<AuthGuard><FeedPage /></AuthGuard>} index />
        <Route element={<AuthGuard><GroupsPage /></AuthGuard>} path="groups" />
        <Route element={<AuthGuard><GroupDetailPage /></AuthGuard>} path="groups/:id" />
        <Route element={<AuthGuard><EventsPage /></AuthGuard>} path="events" />
        <Route element={<ProtectedRoute><CreateEventPage /></ProtectedRoute>} path="events/create" />
        <Route element={<AuthGuard><EventDetailPage /></AuthGuard>} path="events/:id" />
        <Route element={<AuthGuard><JobsPage /></AuthGuard>} path="jobs" />
        <Route element={<ProtectedRoute><CreateJobPage /></ProtectedRoute>} path="jobs/create" />
        <Route element={<AuthGuard><JobDetailPage /></AuthGuard>} path="jobs/:id" />
        <Route element={<AuthGuard><PostDetailPage /></AuthGuard>} path="posts/:id" />
        <Route element={<ProtectedRoute><CreatePostPage /></ProtectedRoute>} path="posts/create" />
        <Route element={<ProtectedRoute><ProfilePage /></ProtectedRoute>} path="profile" />
        <Route element={<ProtectedRoute><EditProfilePage /></ProtectedRoute>} path="profile/edit" />
        <Route element={<ProtectedRoute><ChangePasswordPage /></ProtectedRoute>} path="profile/password" />
        <Route element={<ProtectedRoute><SessionsPage /></ProtectedRoute>} path="profile/sessions" />
        <Route element={<ProtectedRoute><DeleteAccountPage /></ProtectedRoute>} path="profile/delete" />
        <Route element={<ProtectedRoute><MessagesPage /></ProtectedRoute>} path="messages" />
        <Route element={<ProtectedRoute><NotificationsPage /></ProtectedRoute>} path="notifications" />
        <Route element={<ProtectedRoute><WorkspacePlaceholderPage kind="saved" /></ProtectedRoute>} path="saved" />
        <Route element={<NotFoundPage />} path="*" />
      </Route>
      <Route element={<LoginPage />} path="/login" />
      <Route element={<RegisterPage />} path="/register" />
      <Route element={<WelcomePage />} path="/welcome" />
      <Route element={<ForgotPasswordPage />} path="/forgot-password" />
      <Route element={<ResetPasswordPage />} path="/reset-password/:token" />
      <Route element={<EmailVerificationPage />} path="/verify-email/:id/:hash" />
    </Routes>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <BrowserRouter>
          <AppRoutes />
        </BrowserRouter>
      </AuthProvider>
    </QueryClientProvider>
  );
}
