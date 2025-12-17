import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { 
  Dashboard, SportsEsports, School, Settings, Logout, Extension 
} from '@mui/icons-material';
import { useAuth } from '../../contexts/AuthContext';

const Sidebar = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { logout } = useAuth();

  const menuItems = [
    { text: 'Dashboard', icon: <Dashboard />, path: '/' },
    { text: 'Play', icon: <SportsEsports />, path: '/play' },
    { text: 'Training', icon: <School />, path: '/training' },
    { text: 'Settings', icon: <Settings />, path: '/settings' },
  ];

  return (
    <div className="w-[180px] bg-[#211F1C] h-screen fixed left-0 top-0 flex flex-col border-r border-[#383531] z-50">
      {/* Logo Area */}
      <div className="h-[60px] flex items-center px-4 border-b border-[#383531]">
        <Extension className="text-[#2C75FF] mr-2" />
        <span className="font-bold text-white text-lg tracking-tight">CHESS<span className="text-[#2C75FF]">MASTER</span></span>
      </div>

      {/* Navigation */}
      <div className="flex-1 py-4 flex flex-col gap-1 px-2">
        {menuItems.map((item) => {
          const isActive = location.pathname === item.path;
          return (
            <button
              key={item.text}
              onClick={() => navigate(item.path)}
              className={`
                w-full flex items-center px-3 py-3 text-sm font-semibold transition-all duration-200 rounded-sm
                ${isActive 
                  ? 'bg-[#2C75FF] text-white shadow-[0_0_15px_rgba(44,117,255,0.4)]' 
                  : 'text-[#909090] hover:bg-[#383531] hover:text-white hover:pl-4'}
              `}
            >
              <span className={`mr-3 ${isActive ? 'text-white' : 'text-[#818181]'}`}>{item.icon}</span>
              {item.text}
            </button>
          );
        })}
      </div>

      {/* Footer / Logout */}
      <div className="p-4 border-t border-[#383531]">
        <button 
          onClick={() => { logout(); navigate('/login'); }}
          className="w-full flex items-center justify-center py-2 text-[#909090] hover:text-[#FF4D4D] hover:bg-[#2F2020] transition-colors rounded-sm text-sm font-semibold"
        >
          <Logout fontSize="small" className="mr-2" />
          Logout
        </button>
      </div>
    </div>
  );
};

export default Sidebar;