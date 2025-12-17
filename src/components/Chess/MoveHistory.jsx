import React, { useEffect, useRef } from 'react';
import { Paper, Typography, Box } from '@mui/material';

const MoveHistory = ({ history }) => {
  const scrollRef = useRef(null);

  // Auto-scroll to bottom when new moves are added
  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
    }
  }, [history]);

  return (
    <Paper className="flex flex-col h-full max-h-[300px] overflow-hidden">
      <div className="p-3 border-b bg-slate-50">
        <Typography variant="subtitle2" className="font-bold text-slate-700">Move History</Typography>
      </div>
      
      <Box ref={scrollRef} className="flex-grow overflow-y-auto p-2 bg-white">
        <div className="grid grid-cols-[3rem_1fr_1fr] gap-y-1 text-sm">
          {history.map((move, i) => {
            if (i % 2 === 0) {
              return (
                <React.Fragment key={i}>
                  <div className="text-slate-400 text-right pr-2 py-1 bg-slate-50">
                    {Math.floor(i / 2) + 1}.
                  </div>
                  <div className="font-medium text-slate-800 px-2 py-1 hover:bg-blue-50 cursor-pointer rounded">
                    {move}
                  </div>
                  {history[i + 1] ? (
                    <div className="font-medium text-slate-800 px-2 py-1 hover:bg-blue-50 cursor-pointer rounded">
                      {history[i + 1]}
                    </div>
                  ) : <div></div>}
                </React.Fragment>
              );
            }
            return null;
          })}
        </div>
      </Box>
    </Paper>
  );
};

export default MoveHistory;