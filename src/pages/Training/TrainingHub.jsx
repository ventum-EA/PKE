import React from 'react';
import { Grid, Card, CardContent, Typography, Button, CardActions } from '@mui/material';
import { Psychology, Book, Extension } from '@mui/icons-material';

const modules = [
  { title: "Daily Puzzles", desc: "Tactical exercises based on your recent mistakes.", icon: <Extension fontSize="large"/>, color: "text-orange-500" },
  { title: "Opening Lab", desc: "Practice your repertoire against the engine.", icon: <Book fontSize="large"/>, color: "text-blue-500" },
  { title: "Endgame Drills", desc: "Master King & Pawn vs King scenarios.", icon: <Psychology fontSize="large"/>, color: "text-purple-500" },
];

const TrainingHub = () => {
  return (
    <div>
      <Typography variant="h4" className="mb-6 font-bold text-slate-800">Training Center</Typography>
      <Grid container spacing={4}>
        {modules.map((mod, i) => (
          <Grid item xs={12} md={4} key={i}>
            <Card className="h-full flex flex-col hover:shadow-lg transition-shadow">
              <CardContent className="flex-grow text-center pt-8">
                <div className={`mb-4 ${mod.color}`}>{mod.icon}</div>
                <Typography variant="h5" component="div" className="mb-2 font-bold">
                  {mod.title}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {mod.desc}
                </Typography>
              </CardContent>
              <CardActions className="justify-center pb-6">
                <Button size="large" variant="contained">Start Session</Button>
              </CardActions>
            </Card>
          </Grid>
        ))}
      </Grid>
    </div>
  );
};

export default TrainingHub;