import React, { useState } from "react";
import { Icon } from "@iconify/react";
import { useNavigate } from "react-router-dom";
import {
  Button,
  Menu,
  MenuItem,
  Avatar,
  IconButton,
  Drawer,
  List,
  ListItem,
  ListItemButton,
  ListItemText,
  Box
} from "@mui/material";
import { useAuth } from "../../contexts/AuthContext";
// import DrawerList from "./DrawerList"; // You can create a separate file or keep it internal

const navLinks = [
  { name: "Sākums", path: "/", icon: "material-symbols:home-outline" },
  { name: "Spēlēt", path: "/play", icon: "fa6-solid:chess-knight" },
  { name: "Mācīties", path: "/training", icon: "material-symbols:school-outline" },
  { name: "Analīze", path: "/analysis", icon: "material-symbols:analytics-outline" },
];

const Header = () => {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const [anchorEl, setAnchorEl] = useState(null);
  const [mobileOpen, setMobileOpen] = useState(false);

  const handleMenuOpen = (event) => setAnchorEl(event.currentTarget);
  const handleMenuClose = () => setAnchorEl(null);
  
  const handleLogout = () => {
    logout();
    handleMenuClose();
    navigate("/");
  };

  const DrawerList = () => (
    <Box sx={{ width: 250, bgcolor: '#262421', height: '100%', color: 'white' }} role="presentation" onClick={() => setMobileOpen(false)}>
      <List>
        {navLinks.map((link) => (
          <ListItem key={link.name} disablePadding>
            <ListItemButton onClick={() => navigate(link.path)}>
              <Icon icon={link.icon} width="24" height="24" className="mr-4 text-[#2C75FF]" />
              <ListItemText primary={link.name} />
            </ListItemButton>
          </ListItem>
        ))}
      </List>
    </Box>
  );

  return (
    <header className="fixed w-full top-0 z-50 bg-[#302E2B]/80 backdrop-blur-md border-b border-white/10 shadow-lg transition-all duration-300">
      <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-20">
          
          {/* LOGO */}
          <div 
            className="flex items-center gap-2 cursor-pointer" 
            onClick={() => navigate("/")}
          >
             <Icon icon="fa6-solid:chess" className="text-[#2C75FF] text-3xl" />
             <span className="text-xl font-bold tracking-wider text-white">
               CHESS<span className="text-[#2C75FF]">MASTER</span>
             </span>
          </div>

          {/* DESKTOP NAV */}
          <div className="hidden md:flex space-x-8 items-center">
            {navLinks.map((link) => (
              <a
                key={link.name}
                onClick={() => navigate(link.path)}
                className="text-gray-300 hover:text-[#2C75FF] transition-colors cursor-pointer text-sm font-medium uppercase tracking-wide flex items-center gap-2"
              >
                <Icon icon={link.icon} width="20" />
                {link.name}
              </a>
            ))}
          </div>

          {/* AUTH ACTIONS */}
          <div className="flex items-center gap-4">
            {user ? (
              <>
                <IconButton onClick={handleMenuOpen} sx={{ p: 0 }}>
                  <Avatar sx={{ bgcolor: '#2C75FF' }}>
                    {user.name ? user.name[0] : 'U'}
                  </Avatar>
                </IconButton>
                <Menu
                  anchorEl={anchorEl}
                  open={Boolean(anchorEl)}
                  onClose={handleMenuClose}
                  PaperProps={{
                    style: { backgroundColor: '#262421', color: 'white', border: '1px solid #444' }
                  }}
                >
                  <MenuItem onClick={() => navigate("/dashboard")}>Vadības Panelis</MenuItem>
                  <MenuItem onClick={handleLogout} sx={{ color: '#ff6b6b' }}>Iziet</MenuItem>
                </Menu>
              </>
            ) : (
              <Button
                variant="contained"
                onClick={() => navigate("/login")}
                sx={{
                  backgroundColor: '#2C75FF',
                  animation: "slideDown 0.8s",
                  '&:hover': { backgroundColor: '#1a60e0' }
                }}
              >
                Pieslēgties
              </Button>
            )}

            {/* MOBILE MENU TOGGLE */}
            <div className="md:hidden">
              <IconButton onClick={() => setMobileOpen(true)} sx={{ color: 'white' }}>
                <Icon icon="material-symbols:menu" width="32" />
              </IconButton>
            </div>
          </div>
        </div>
      </nav>

      <Drawer anchor="right" open={mobileOpen} onClose={() => setMobileOpen(false)}>
        <DrawerList />
      </Drawer>

      <style>{`
        @keyframes slideDown {
          from { transform: translateY(-30px); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
        }
      `}</style>
    </header>
  );
};

export default Header;