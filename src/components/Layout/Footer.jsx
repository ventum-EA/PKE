import React from 'react';
import { Typography, Rating } from "@mui/material";
import { Icon } from "@iconify/react";
import { useNavigate } from "react-router-dom";

const Footer = () => {
  const navigate = useNavigate();
  
  return (
    // Changed from orange gradient to Dark Theme Gradient
    <footer className="bg-gradient-to-b from-[#262421] to-[#151515] py-12 px-4 sm:px-6 lg:px-8 border-t border-white/5">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          
          {/* BRAND COLUMN */}
          <div className="space-y-4">
            <div className="flex items-center gap-2">
               <Icon icon="fa6-solid:chess" className="text-[#2C75FF] text-2xl" />
               <Typography
                variant="h6"
                sx={{ color: "#fff", fontWeight: 700, letterSpacing: 1 }}
               >
                CHESSMASTER
               </Typography>
            </div>
            <Typography variant="body2" sx={{ color: "#a3a3a3", lineHeight: 1.6 }}>
              Uzlabo savas prasmes ar Stockfish 16 analīzi un personalizētiem treniņiem.
            </Typography>
            <Rating value={5} readOnly sx={{ "& .MuiRating-iconFilled": { color: "#2C75FF" } }} />
          </div>

          {/* CONTACT INFO */}
          <div>
            <Typography variant="h6" sx={{ color: "#fff", fontWeight: 600, mb: 2 }}>
              Kontakti
            </Typography>
            <div className="space-y-3 text-[#a3a3a3] text-sm">
              <p className="flex items-center gap-2">
                <Icon icon="material-symbols:phone-enabled" className="text-[#2C75FF]" />
                +371 20000000
              </p>
              <p className="flex items-center gap-2">
                <Icon icon="material-symbols:mail-outline" className="text-[#2C75FF]" />
                info@chessmaster.lv
              </p>
              <p className="flex items-center gap-2">
                <Icon icon="gg:pin" className="text-[#2C75FF]" />
                Rīga, Latvija
              </p>
            </div>
          </div>

          {/* QUICK LINKS */}
          <div>
            <Typography variant="h6" sx={{ color: "#fff", fontWeight: 600, mb: 2 }}>
              Sadaļas
            </Typography>
            <ul className="space-y-2 text-[#a3a3a3] text-sm">
              <li><a href="#" className="hover:text-[#2C75FF] transition">Sākums</a></li>
              <li><a href="#" className="hover:text-[#2C75FF] transition">Cenrādis</a></li>
              <li><a href="#" className="hover:text-[#2C75FF] transition">Turnīri</a></li>
              <li><a href="#" className="hover:text-[#2C75FF] transition">Par Mums</a></li>
            </ul>
          </div>

          {/* SOCIALS */}
          <div>
            <Typography variant="h6" sx={{ color: "#fff", fontWeight: 600, mb: 2 }}>
              Sekojiet Mums
            </Typography>
            <div className="flex space-x-4 text-white">
              <a href="#" className="hover:text-[#2C75FF] transition transform hover:scale-110">
                <Icon icon="mdi:instagram" width="28" />
              </a>
              <a href="#" className="hover:text-[#2C75FF] transition transform hover:scale-110">
                <Icon icon="ic:baseline-facebook" width="28" />
              </a>
              <a href="#" className="hover:text-[#2C75FF] transition transform hover:scale-110">
                <Icon icon="prime:twitter" width="28" />
              </a>
            </div>
          </div>
        </div>

        <div className="mt-12 pt-6 border-t border-white/10 text-center">
          <Typography variant="body2" sx={{ color: "#666" }}>
            &copy; {new Date().getFullYear()} ChessMaster. Visas tiesības aizsargātas.
          </Typography>
        </div>
      </div>
    </footer>
  );
};

export default Footer;