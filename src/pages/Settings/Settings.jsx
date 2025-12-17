import React from 'react';
import { 
  Paper, Typography, Box, Switch, FormControlLabel, 
  Slider, Select, MenuItem, Button, Divider, Grid
} from '@mui/material';
import { Icon } from "@iconify/react";
import { useSettings } from '../../contexts/SettingsContext';

const SettingsSection = ({ title, icon, children }) => (
  <Paper sx={{ p: 4, mb: 3, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2 }}>
    <Box display="flex" alignItems="center" gap={2} mb={3}>
      <Box p={1} bgcolor="rgba(44, 117, 255, 0.1)" borderRadius={1} display="flex">
        <Icon icon={icon} width={24} color="#2C75FF" />
      </Box>
      <Typography variant="h6" color="white" fontWeight={600}>{title}</Typography>
    </Box>
    {children}
  </Paper>
);

const Settings = () => {
  const { settings, updateSetting } = useSettings();

  return (
    <div className="max-w-4xl mx-auto pb-10 animate-fade-in">
      <Typography variant="h4" fontWeight="bold" color="white" mb={4}>
        Settings
      </Typography>

      {/* Board & Gameplay */}
      <SettingsSection title="Board & Gameplay" icon="fa6-solid:chess-board">
        <Grid container spacing={2}>
           <Grid item xs={12} md={6}>
             <FormControlLabel
               control={
                 <Switch 
                   checked={settings.autoQueen} 
                   onChange={(e) => updateSetting('autoQueen', e.target.checked)} 
                   sx={{ '& .MuiSwitch-switchBase.Mui-checked': { color: '#2C75FF' }, '& .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track': { backgroundColor: '#2C75FF' } }}
                 />
               }
               label={<Typography color="#e5e5e5">Auto-Promote to Queen</Typography>}
             />
           </Grid>
           <Grid item xs={12} md={6}>
             <FormControlLabel
               control={
                 <Switch 
                   checked={settings.showCoordinates} 
                   onChange={(e) => updateSetting('showCoordinates', e.target.checked)}
                   sx={{ '& .MuiSwitch-switchBase.Mui-checked': { color: '#2C75FF' }, '& .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track': { backgroundColor: '#2C75FF' } }} 
                 />
               }
               label={<Typography color="#e5e5e5">Show Coordinates</Typography>}
             />
           </Grid>
        </Grid>
      </SettingsSection>

      {/* Stockfish Engine */}
      <SettingsSection title="Stockfish Engine" icon="material-symbols:memory">
        <Box mb={4}>
          <Box display="flex" justifyContent="space-between">
            <Typography color="#e5e5e5" gutterBottom>Analysis Depth</Typography>
            <Typography color="#2C75FF" fontWeight="bold">{settings.engineDepth}</Typography>
          </Box>
          <Slider
            value={settings.engineDepth}
            min={10}
            max={30}
            step={1}
            onChange={(e, v) => updateSetting('engineDepth', v)}
            sx={{ color: '#2C75FF' }}
          />
          <Typography variant="caption" color="text.secondary">
            Higher depth is more accurate (Grandmaster level) but uses more CPU.
          </Typography>
        </Box>

        <Divider sx={{ borderColor: '#383531', mb: 3 }} />

        <Box display="flex" alignItems="center" gap={3}>
           <Typography color="#e5e5e5">Engine Threads</Typography>
           <Select
             value={settings.engineThreads}
             onChange={(e) => updateSetting('engineThreads', e.target.value)}
             size="small"
             sx={{ 
                color: 'white', 
                '.MuiOutlinedInput-notchedOutline': { borderColor: '#383531' },
                '&:hover .MuiOutlinedInput-notchedOutline': { borderColor: '#666' },
                width: 150 
             }}
           >
             <MenuItem value={1}>1 Thread</MenuItem>
             <MenuItem value={2}>2 Threads</MenuItem>
             <MenuItem value={4}>4 Threads</MenuItem>
             <MenuItem value={8}>8 Threads</MenuItem>
           </Select>
        </Box>
      </SettingsSection>

      {/* Interface & Sound */}
      <SettingsSection title="Interface" icon="material-symbols:palette-outline">
        <FormControlLabel
          control={
            <Switch 
              checked={settings.soundEnabled} 
              onChange={(e) => updateSetting('soundEnabled', e.target.checked)} 
              sx={{ '& .MuiSwitch-switchBase.Mui-checked': { color: '#2C75FF' }, '& .MuiSwitch-switchBase.Mui-checked + .MuiSwitch-track': { backgroundColor: '#2C75FF' } }} 
            />
          }
          label={<Typography color="#e5e5e5">Sound Effects</Typography>}
        />
        
        <Box mt={4} display="flex" justifyContent="flex-end">
           <Button 
             variant="outlined" 
             color="error" 
             startIcon={<Icon icon="material-symbols:restart-alt" />}
             onClick={() => {
                if(confirm("Reset all settings to default?")) {
                    localStorage.removeItem('chess_settings');
                    window.location.reload();
                }
             }}
           >
             Reset Defaults
           </Button>
        </Box>
      </SettingsSection>
    </div>
  );
};

export default Settings;