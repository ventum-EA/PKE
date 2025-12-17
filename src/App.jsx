import React from 'react';
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import { ThemeProvider } from '@mui/material/styles';
import { theme } from './theme';

// Contexts
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { SettingsProvider } from './contexts/SettingsContext';

// Layouts
import DashboardLayout from './components/Layout/DashboardLayout'; // The new file above

// Pages
import Login from './pages/Auth/Login';
import Register from './pages/Auth/Register';
import Dashboard from './pages/Dashboard/Dashboard';
import Play from './pages/Game/Play';
import Analysis from './pages/Game/Analysis';
import Settings from './pages/Settings/Settings';

// --- ROUTE GUARDS ---

const ProtectedRoute = () => {
  const { user, loading } = useAuth();
  if (loading) return <div>Loading...</div>; // Or a spinner
  return user ? <Outlet /> : <Navigate to="/login" replace />;
};

const PublicOnlyRoute = () => {
  const { user, loading } = useAuth();
  if (loading) return <div>Loading...</div>;
  return !user ? <Outlet /> : <Navigate to="/dashboard" replace />;
};

// --- APP COMPONENT ---

function App() {
  return (
    <ThemeProvider theme={theme}>
      <SettingsProvider>
        <AuthProvider>
          <BrowserRouter>
            <Routes>
              
              {/* PUBLIC ROUTES (Login, Register) */}
              <Route element={<PublicOnlyRoute />}>
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />
              </Route>

              {/* PROTECTED APP ROUTES */}
              <Route element={<ProtectedRoute />}>
                {/* Wrap these in the DashboardLayout */}
                <Route element={<DashboardLayout />}>
                  <Route path="/" element={<Navigate to="/dashboard" replace />} />
                  <Route path="dashboard" element={<Dashboard />} />
                  <Route path="play" element={<Play />} />
                  <Route path="analysis" element={<Analysis />} />
                  <Route path="settings" element={<Settings />} />
                </Route>
              </Route>

              {/* FALLBACK */}
              <Route path="*" element={<Navigate to="/login" replace />} />

            </Routes>
          </BrowserRouter>
        </AuthProvider>
      </SettingsProvider>
    </ThemeProvider>
  );
}

export default App;