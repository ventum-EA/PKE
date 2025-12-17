import React, { useState, useEffect } from "react";
import { Chess } from "chess.js";
import { 
  Paper, Typography, Box, ToggleButton, ToggleButtonGroup, 
  IconButton, Button, Divider, TextField // <--- Added Divider here
} from "@mui/material";
import { Icon } from "@iconify/react";
import { useStockfish } from "../../hooks/useStockfish"; 
import GameBoard from "../../components/Chess/GameBoard";
import AnalysisPanel from "../../components/Chess/AnalysisPanel";

const Analysis = () => {
  const [game, setGame] = useState(new Chess());
  const [sideToAnalyze, setSideToAnalyze] = useState("both"); // 'white', 'black', 'both'
  const { evaluation, bestMove, analyze } = useStockfish();

  // Trigger analysis when board updates
  useEffect(() => {
    const turn = game.turn(); // 'w' or 'b'
    const shouldAnalyze = 
      sideToAnalyze === 'both' || 
      (sideToAnalyze === 'white' && turn === 'w') || 
      (sideToAnalyze === 'black' && turn === 'b');

    if (shouldAnalyze) {
      analyze(game.fen());
    }
  }, [game, sideToAnalyze]);

  // Handle Piece Drop (Play both sides)
  const onDrop = (source, target) => {
    try {
      const move = game.move({ from: source, to: target, promotion: "q" });
      if (!move) return false;
      setGame(new Chess(game.fen())); // Force re-render
      return true;
    } catch (e) { return false; }
  };

  const handleUndo = () => {
    game.undo();
    setGame(new Chess(game.fen()));
  };

  const handleReset = () => {
    setGame(new Chess());
  };

  return (
    <div className="h-[calc(100vh-100px)] flex flex-col xl:flex-row gap-6 animate-fade-in">
      
      {/* LEFT: Board Area */}
      <Paper 
        sx={{ 
          flex: 1, 
          bgcolor: '#262421', 
          border: '1px solid #383531', 
          borderRadius: 2,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          position: 'relative',
          p: 4
        }}
      >
        <div className="w-full max-w-[75vh] aspect-square">
          <GameBoard 
            game={game} 
            onPieceDrop={onDrop}
          />
        </div>

        {/* Floating Controls */}
        <Box 
          sx={{ 
            position: 'absolute', 
            bottom: 20, 
            bgcolor: 'rgba(30, 29, 27, 0.9)', 
            border: '1px solid #383531',
            borderRadius: 3,
            px: 2, py: 1,
            display: 'flex',
            gap: 1,
            backdropFilter: 'blur(4px)'
          }}
        >
           <IconButton onClick={handleUndo} sx={{ color: 'white' }}><Icon icon="material-symbols:undo" /></IconButton>
           <IconButton onClick={handleReset} sx={{ color: '#f87171' }}><Icon icon="material-symbols:refresh" /></IconButton>
           <IconButton sx={{ color: 'white' }} onClick={() => {
              // Flip logic placeholder
              console.log("Flip board feature to be implemented");
           }}><Icon icon="material-symbols:flip-camera-android" /></IconButton>
        </Box>
      </Paper>

      {/* RIGHT: Analysis Tools */}
      <div className="w-full xl:w-[400px] flex flex-col gap-4">
        
        {/* 1. Evaluation */}
        <AnalysisPanel evaluation={evaluation} bestMove={bestMove} />

        {/* 2. Controls */}
        <Paper sx={{ p: 3, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2 }}>
          <Typography variant="subtitle2" color="text.secondary" mb={2} textTransform="uppercase">
            Analysis Settings
          </Typography>
          
          <ToggleButtonGroup
            value={sideToAnalyze}
            exclusive
            onChange={(e, v) => v && setSideToAnalyze(v)}
            fullWidth
            sx={{ 
              mb: 3,
              '& .MuiToggleButton-root': { 
                color: '#888', 
                borderColor: '#383531',
                '&.Mui-selected': { color: 'white', bgcolor: '#2C75FF' } 
              } 
            }}
          >
            <ToggleButton value="white">White</ToggleButton>
            <ToggleButton value="both">Both</ToggleButton>
            <ToggleButton value="black">Black</ToggleButton>
          </ToggleButtonGroup>

          <Divider sx={{ borderColor: '#383531', mb: 3 }} />

          <Typography variant="subtitle2" color="text.secondary" mb={1}>
            FEN String
          </Typography>
          <Box 
            sx={{ 
              bgcolor: '#1E1D1B', 
              p: 2, 
              borderRadius: 1, 
              border: '1px solid #333',
              fontFamily: 'monospace',
              fontSize: '0.8rem',
              color: '#2C75FF',
              wordBreak: 'break-all'
            }}
          >
            {game.fen()}
          </Box>
          <Button 
            fullWidth 
            size="small" 
            sx={{ mt: 1, color: '#888' }}
            startIcon={<Icon icon="material-symbols:content-copy" />}
            onClick={() => navigator.clipboard.writeText(game.fen())}
          >
            Copy FEN
          </Button>
        </Paper>

        {/* 3. History */}
        <Paper sx={{ flex: 1, p: 2, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2, overflowY: 'auto' }}>
            <Typography variant="h6" color="white" mb={2}>Move History</Typography>
            <div className="grid grid-cols-2 gap-y-1 text-sm">
                {game.history().map((move, i) => (
                    <React.Fragment key={i}>
                        {i % 2 === 0 && <span className="text-gray-500 text-right pr-2">{(i/2)+1}.</span>}
                        <span className="text-gray-300 font-medium hover:bg-[#333] px-2 rounded cursor-pointer">{move}</span>
                    </React.Fragment>
                ))}
            </div>
        </Paper>
      </div>
    </div>
  );
};

export default Analysis;