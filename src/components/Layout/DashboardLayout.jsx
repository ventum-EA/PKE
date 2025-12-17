import * as React from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { createTheme } from '@mui/material/styles';
import { DashboardLayout } from '@toolpad/core/DashboardLayout';
import { ReactRouterAppProvider } from '@toolpad/core/react-router';
import { Icon } from '@iconify/react';
import { useAuth } from '../../contexts/AuthContext';

// --- NAVIGATION CONFIG ---
// These segments must match the paths in App.jsx
const NAVIGATION = [
  {
    kind: 'header',
    title: 'Galvenā',
  },
  {
    segment: 'dashboard',
    title: 'Pārskats',
    icon: <Icon icon="material-symbols:dashboard-outline" width="24" />,
  },
  {
    segment: 'play',
    title: 'Spēlēt',
    icon: <Icon icon="fa6-solid:chess-board" width="24" />,
  },
  {
    kind: 'divider',
  },
  {
    kind: 'header',
    title: 'Rīki',
  },
  {
    segment: 'analysis',
    title: 'Analīze',
    icon: <Icon icon="material-symbols:analytics-outline" width="24" />,
  },
  {
    segment: 'settings',
    title: 'Iestatījumi',
    icon: <Icon icon="material-symbols:settings-outline" width="24" />,
  },
];

// --- THEME CONFIG ---
// Forces the dashboard to use your Cherenkov Blue palette
const dashboardTheme = createTheme({
  cssVariables: {
    colorSchemeSelector: 'data-toolpad-color-scheme',
  },
  colorSchemes: {
    dark: {
      palette: {
        primary: {
          main: '#2C75FF', // Cherenkov Blue
        },
        background: {
          default: '#302E2B', // Chess.com Dark
          paper: '#262421',   // Card Dark
        },
        text: {
          primary: '#e5e5e5',
          secondary: '#a3a3a3',
        },
      },
    },
  },
  breakpoints: {
    values: { xs: 0, sm: 600, md: 900, lg: 1200, xl: 1536 },
  },
});

const Layout = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  // Adapter to link Toolpad's internal logic with your AuthContext
  const session = React.useMemo(() => {
    return {
      user: {
        name: user?.name || 'Spēlētājs',
        email: user?.email || '',
        image: user?.avatar || '',
      },
    };
  }, [user]);

  const authentication = React.useMemo(() => {
    return {
      signIn: () => navigate('/login'),
      signOut: () => {
        logout();
        navigate('/login');
      },
    };
  }, [navigate, logout]);

  return (
    <ReactRouterAppProvider
      navigation={NAVIGATION}
      authentication={authentication}
      session={session}
      branding={{
        logo: <Icon icon="fa6-solid:chess" width={32} color="#2C75FF" />,
        title: 'ChessMaster',
      }}
      theme={dashboardTheme}
    >
      <DashboardLayout defaultSidebarCollapsed>
        {/* Page Content Rendered Here */}
        <div className="p-6 w-full min-h-screen bg-[#302E2B] text-[#e5e5e5]">
           <Outlet />
        </div>
      </DashboardLayout>
    </ReactRouterAppProvider>
  );
};

export default Layout;