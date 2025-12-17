import React from 'react';
import { Paper, Switch, FormControlLabel, Typography, Divider, Button } from '@mui/material';

const Settings = () => {
  return (
    <div className="max-w-2xl mx-auto">
      <Typography variant="h4" className="mb-6 font-bold">Settings</Typography>
      
      <Paper className="p-6 space-y-6">
        <div>
          <Typography variant="h6" className="mb-2">Interface</Typography>
          <FormControlLabel control={<Switch />} label="Dark Mode" />
          <FormControlLabel control={<Switch defaultChecked />} label="Show Coordinates" />
          <FormControlLabel control={<Switch defaultChecked />} label="Highlight Valid Moves" />
        </div>
        
        <Divider />
        
        <div>
          <Typography variant="h6" className="mb-2">Engine Analysis</Typography>
          <FormControlLabel control={<Switch defaultChecked />} label="Auto-Analyze after game" />
          <Typography variant="body2" color="textSecondary" className="mt-2">
            Engine Depth: 15 (Balanced)
          </Typography>
        </div>

        <Divider />

        <div>
          <Typography variant="h6" className="mb-2 text-red-600">Danger Zone</Typography>
          <Button variant="outlined" color="error">Reset Statistics</Button>
        </div>
      </Paper>
    </div>
  );
};

export default Settings;