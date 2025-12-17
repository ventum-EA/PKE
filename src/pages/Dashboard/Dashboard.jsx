import React from 'react';
import { 
  Grid, Paper, Typography, Box, LinearProgress, Table, 
  TableBody, TableCell, TableContainer, TableHead, TableRow 
} from '@mui/material';
import { 
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, 
  PieChart, Pie, Cell, BarChart, Bar, Legend 
} from 'recharts';
import { Icon } from "@iconify/react";

// --- MOCK DATA ---
const ratingData = [
  { month: 'Jan', rating: 1200 }, { month: 'Feb', rating: 1255 },
  { month: 'Mar', rating: 1240 }, { month: 'Apr', rating: 1310 },
  { month: 'May', rating: 1295 }, { month: 'Jun', rating: 1350 },
  { month: 'Jul', rating: 1410 }, { month: 'Aug', rating: 1380 },
];
const winStats = [
  { name: 'Wins', value: 55, color: '#2C75FF' },
  { name: 'Draws', value: 15, color: '#64748b' },
  { name: 'Losses', value: 30, color: '#dc2626' },
];
const sideStats = [
  { name: 'White', wins: 40, loss: 20 },
  { name: 'Black', wins: 32, loss: 28 },
];
const openings = [
  { name: "Sicilian Defense", games: 45, win: 58 },
  { name: "Queen's Gambit", games: 32, win: 62 },
  { name: "Ruy Lopez", games: 28, win: 45 },
  { name: "London System", games: 15, win: 50 },
  { name: "Caro-Kann", games: 12, win: 48 },
];

const StatCard = ({ title, value, icon, trend }) => (
  <Paper sx={{ p: 3, height: '100%', bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2, display: 'flex', flexDirection: 'column', justifyContent: 'space-between' }}>
    <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
      <Typography variant="subtitle2" color="text.secondary" sx={{ textTransform: 'uppercase', letterSpacing: 1, fontSize: '0.75rem' }}>
        {title}
      </Typography>
      <Box p={1} bgcolor="rgba(44, 117, 255, 0.1)" borderRadius="50%">
        <Icon icon={icon} width={20} color="#2C75FF" />
      </Box>
    </Box>
    <div>
        <Typography variant="h4" fontWeight="bold" color="white" mb={0.5}>
        {value}
        </Typography>
        {trend && (
        <Box display="flex" alignItems="center" gap={0.5}>
            <Icon icon="material-symbols:trending-up" color="#4ade80" width={16} />
            <Typography variant="caption" sx={{ color: '#4ade80', fontWeight: 600 }}>{trend}</Typography>
        </Box>
        )}
    </div>
  </Paper>
);

const Dashboard = () => {
  return (
    <div className="w-[40%] space-y-6 animate-fade-in pb-10">
      
      {/* 1. STATS OVERVIEW CARDS (Grid keeps them evenly distributed) */}
      <Grid container spacing={3}>
        <Grid item xs={12} sm={6} lg={3}>
          <StatCard title="Current Rating" value="1350" icon="fa6-solid:chess-king" trend="+55 this month" />
        </Grid>
        <Grid item xs={12} sm={6} lg={3}>
          <StatCard title="Win Rate" value="55%" icon="fa6-solid:trophy" />
        </Grid>
        <Grid item xs={12} sm={6} lg={3}>
          <StatCard title="Games Played" value="842" icon="fa6-solid:chess-board" />
        </Grid>
        <Grid item xs={12} sm={6} lg={3}>
          <StatCard title="Puzzles Solved" value="1204" icon="material-symbols:extension" trend="+12 today" />
        </Grid>
      </Grid>

      {/* 2. CHARTS AREA - FLEX LAYOUT FOR 40% WIDTH */}
      
      {/* Row 1: Rating & Pie */}
      <div className="flex flex-col lg:flex-row justify-center gap-6 w-full">
        
        {/* Rating History (40%) */}
        <div className="w-[40%]">
          <Paper sx={{ p: 4, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2, height: 400 }}>
            <Box display="flex" justifyContent="space-between" alignItems="center" mb={4}>
                <Typography variant="h6" color="white" fontWeight={600}>Rating Progression</Typography>
            </Box>
            <ResponsiveContainer width="100%" height="85%">
              <AreaChart data={ratingData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorRating" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#2C75FF" stopOpacity={0.4}/>
                    <stop offset="95%" stopColor="#2C75FF" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#333" vertical={false} />
                <XAxis dataKey="month" stroke="#666" axisLine={false} tickLine={false} tick={{fill: '#888', fontSize: 12}} />
                <YAxis stroke="#666" axisLine={false} tickLine={false} tick={{fill: '#888', fontSize: 12}} />
                <Tooltip 
                    contentStyle={{ backgroundColor: '#1E1D1B', borderColor: '#383531', color: '#fff', borderRadius: 8 }} 
                    itemStyle={{ color: '#2C75FF' }}
                />
                <Area type="monotone" dataKey="rating" stroke="#2C75FF" strokeWidth={3} fillOpacity={1} fill="url(#colorRating)" />
              </AreaChart>
            </ResponsiveContainer>
          </Paper>
        </div>

        {/* Win/Loss Pie (40%) */}
        <div className="w-full lg:w-[40%]">
          <Paper sx={{ p: 4, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2, height: 400, display: 'flex', flexDirection: 'column' }}>
            <Typography variant="h6" color="white" fontWeight={600} mb={2}>Overall Performance</Typography>
            <Box flex={1} position="relative">
                <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Pie 
                        data={winStats} 
                        innerRadius={80} 
                        outerRadius={100} 
                        paddingAngle={5} 
                        dataKey="value"
                        stroke="none"
                    >
                    {winStats.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                    </Pie>
                    <Tooltip contentStyle={{ backgroundColor: '#1E1D1B', borderColor: '#383531', borderRadius: 8 }} />
                    <Legend verticalAlign="bottom" height={36} iconType="circle" />
                </PieChart>
                </ResponsiveContainer>
                <Box sx={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -60%)', textAlign: 'center' }}>
                    <Typography variant="h3" fontWeight="bold" color="white">55%</Typography>
                    <Typography variant="body2" color="text.secondary">Winrate</Typography>
                </Box>
            </Box>
          </Paper>
        </div>
      </div>

      {/* Row 2: Bar Chart & Table */}
      <div className="flex flex-col lg:flex-row justify-center gap-6 w-full">
        
        {/* Winrate by Color (40%) */}
        <div className="w-[40%]">
          <Paper sx={{ p: 4, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2, height: 400 }}>
            <Typography variant="h6" color="white" fontWeight={600} mb={4}>Winrate by Color</Typography>
            <ResponsiveContainer width="100%" height="85%">
              <BarChart data={sideStats} layout="vertical" barSize={30} margin={{ left: -20 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#333" horizontal={false} />
                <XAxis type="number" stroke="#666" hide />
                <YAxis dataKey="name" type="category" stroke="#fff" width={60} tick={{fill: '#e5e5e5', fontSize: 14}} axisLine={false} tickLine={false} />
                <Tooltip cursor={{fill: 'rgba(255,255,255,0.05)'}} contentStyle={{ backgroundColor: '#1E1D1B', borderColor: '#383531' }} />
                <Legend />
                <Bar dataKey="wins" fill="#2C75FF" name="Wins" stackId="a" radius={[4, 0, 0, 4]} />
                <Bar dataKey="loss" fill="#dc2626" name="Losses" stackId="a" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </Paper>
        </div>

        {/* Top Openings Table (40%) */}
        <div className="w-[40%]">
          <Paper sx={{ bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2, height: 400, overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            <Box p={3} borderBottom="1px solid #383531">
                <Typography variant="h6" color="white" fontWeight={600}>Top Openings</Typography>
            </Box>
            <TableContainer sx={{ flex: 1 }}>
              <Table>
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ color: '#888', borderBottom: '1px solid #383531', pl: 4 }}>Opening Name</TableCell>
                    <TableCell sx={{ color: '#888', borderBottom: '1px solid #383531' }} align="right">Games</TableCell>
                    <TableCell sx={{ color: '#888', borderBottom: '1px solid #383531', pr: 4 }}>Win %</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {openings.map((row) => (
                    <TableRow key={row.name} hover sx={{ '&:hover': { bgcolor: '#302E2B' } }}>
                      <TableCell sx={{ color: '#fff', borderBottom: '1px solid #333', pl: 4, fontWeight: 500 }}>{row.name}</TableCell>
                      <TableCell sx={{ color: '#aaa', borderBottom: '1px solid #333' }} align="right">{row.games}</TableCell>
                      <TableCell sx={{ color: '#fff', borderBottom: '1px solid #333', pr: 4 }}>
                        <Box display="flex" alignItems="center" gap={2}>
                          <LinearProgress 
                            variant="determinate" 
                            value={row.win} 
                            sx={{ 
                                width: 80, 
                                height: 8, 
                                borderRadius: 4, 
                                bgcolor: '#333', 
                                '& .MuiLinearProgress-bar': { bgcolor: row.win > 50 ? '#2C75FF' : '#f87171' } 
                            }} 
                          />
                          <Typography variant="body2" sx={{ minWidth: 35, color: row.win > 50 ? '#4ade80' : '#aaa' }}>
                            {row.win}%
                          </Typography>
                        </Box>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          </Paper>
        </div>
      </div>

    </div>
  );
};

export default Dashboard;