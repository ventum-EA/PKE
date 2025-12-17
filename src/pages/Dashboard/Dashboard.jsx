import React, { useEffect, useState } from 'react';
import { Grid, Paper, Typography } from '@mui/material';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { api } from '../../services/api';
import StatCard from '../../components/UI/StatCard';
import { TrendingUp, Extension, SportsEsports } from '@mui/icons-material';
const Dashboard = () => {
  const [data, setData] = useState([]);

  useEffect(() => {
    api.getStats().then(setData);
  }, []);

  return (
    <div className="space-y-6">
      <Typography variant="h4" className="font-bold text-slate-800">Overview</Typography>
      
      {/* KPI Cards */}
      <Grid container spacing={3}>
  <Grid item xs={12} md={4}>
     <StatCard 
       title="Current Rating" 
       value="1280" 
       icon={<TrendingUp fontSize="large" />} 
       color="text-blue-600"
       borderColor="border-blue-600"
     />
  </Grid>
  {/* Repeat for other stats */}
</Grid>

      {/* Progress Chart */}
      <Paper className="p-6 h-[400px]">
        <Typography variant="h6" className="mb-4">Rating History</Typography>
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={data}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis domain={['dataMin - 50', 'dataMax + 50']} />
            <Tooltip />
            <Line type="monotone" dataKey="rating" stroke="#2563eb" strokeWidth={3} />
          </LineChart>
        </ResponsiveContainer>
      </Paper>
    </div>
  );
};

export default Dashboard;