import React from 'react';
import { Paper, Typography, Box } from '@mui/material';

const StatCard = ({ title, value, icon, color = "text-blue-500", borderColor = "border-blue-500" }) => {
  return (
    <Paper 
      elevation={2}
      className={`p-6 flex items-center justify-between border-l-4 ${borderColor} hover:shadow-md transition-shadow`}
    >
      <Box>
        <Typography variant="body2" color="textSecondary" className="font-medium uppercase tracking-wider text-xs mb-1">
          {title}
        </Typography>
        <Typography variant="h4" className="font-bold text-slate-800">
          {value}
        </Typography>
      </Box>
      
      <Box className={`p-3 rounded-full bg-slate-50 ${color}`}>
        {icon}
      </Box>
    </Paper>
  );
};

export default StatCard;