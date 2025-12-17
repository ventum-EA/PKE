import React, { createContext, useContext, useState, useEffect } from 'react';

const SettingsContext = createContext();

export const useSettings = () => useContext(SettingsContext);

export const SettingsProvider = ({ children }) => {
  // Load from local storage or default
  const [settings, setSettings] = useState(() => {
    const saved = localStorage.getItem('chess_settings');
    return saved ? JSON.parse(saved) : {
      theme: 'dark',
      autoQueen: true,
      showCoordinates: true,
      soundEnabled: true,
      engineDepth: 18,
      engineThreads: 2,
    };
  });

  // Save on change
  useEffect(() => {
    localStorage.setItem('chess_settings', JSON.stringify(settings));
  }, [settings]);

  const updateSetting = (key, value) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  return (
    <SettingsContext.Provider value={{ settings, updateSetting }}>
      {children}
    </SettingsContext.Provider>
  );
};