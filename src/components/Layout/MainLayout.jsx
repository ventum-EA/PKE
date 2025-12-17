import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';

const MainLayout = () => {
  return (
    <div className="flex min-h-screen bg-[#302E2B]">
      <Sidebar />
      {/* Added 'flex flex-col items-center' to main to center content horizontally */}
      <main className="ml-[180px] flex-1 p-8 overflow-y-auto flex flex-col items-center">
        <div className="w-full max-w-[1600px]">
          <Outlet />
        </div>
      </main>
    </div>
  );
};

export default MainLayout;