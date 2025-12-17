import React, { useState, useEffect } from "react";
import { Chess } from "chess.js";
import { Button, IconButton, Paper, Typography, ToggleButton, ToggleButtonGroup } from "@mui/material";
import { Icon } from "@iconify/react";
import { useStockfish } from "../../hooks/useStockfish";
import GameBoard from "../../components/Chess/GameBoard";
import AnalysisPanel from "../../components/Chess/AnalysisPanel";

const Analysis = () => {
  const [game, setGame] = useState(new Chess());
  const [fen, setFen] = useState("start");
  const [analyzeSide, setAnalyzeSide] = useState("both"); // 'white', 'black', 'both', 'none'
  
  // Connect to Stockfish
  const { evaluation, bestMove, analyze } = useStockfish();

  // Trigger Analysis when board changes
  useEffect(() => {
    if (analyzeSide === 'none') return;
    
    // Check if we should analyze this turn
    const turn = game.turn(); // 'w' or 'b'
    if (analyzeSide === 'both' || (analyzeSide === 'white' && turn === 'w') || (analyzeSide === 'black' && turn === 'b')) {
       analyze(game.fen());
    }
  }, [game, analyzeSide]);

  // Handle Drop (Allows moving freely)
  function onDrop(sourceSquare, targetSquare) {
    try {
      // Create a temporary game instance to validate move
      const tempGame = new Chess(game.fen());
      
      // Attempt move
      const move = tempGame.move({
        from: sourceSquare,
        to: targetSquare,
        promotion: "q",
      });

      if (move === null) return false;
      
      // Update real state
      setGame(tempGame);
      setFen(tempGame.fen());
      return true;
    } catch (error) {
      return false;
    }
  }

  return (
    <div className="flex flex-col xl:flex-row gap-6 h-[calc(100vh-100px)] animate-fade-in">
      
      {/* LEFT: BOARD */}
      <div className="flex-1 flex justify-center items-center bg-[#262421] rounded-xl border border-[#383531] p-4 relative">
        <div className="w-full max-w-[70vh] aspect-square">
          <GameBoard 
            game={game} 
            onPieceDrop={onDrop} 
            customDarkSquareStyle={{ backgroundColor: '#2C75FF' }} 
            customLightSquareStyle={{ backgroundColor: '#E1E1E1' }}
          />
        </div>
        
        {/* Floating Controls for Reset/Undo */}
        <div className="absolute bottom-6 flex gap-2 bg-[#1E1D1B]/90 p-2 rounded-lg border border-[#383531] backdrop-blur-sm">
           <IconButton onClick={() => { game.undo(); setGame(new Chess(game.fen())); }} sx={{ color: 'white' }}>
             <Icon icon="material-symbols:undo" />
           </IconButton>
           <IconButton onClick={() => setGame(new Chess())} sx={{ color: '#f87171' }}>
             <Icon icon="material-symbols:refresh" />
           </IconButton>
           <IconButton sx={{ color: 'white' }}>
             <Icon icon="material-symbols:flip" />
           </IconButton>
        </div>
      </div>

      {/* RIGHT: ANALYSIS TOOLS */}
      <div className="w-full xl:w-[400px] flex flex-col gap-4">
        
        {/* 1. Evaluation Panel */}
        <AnalysisPanel evaluation={evaluation} bestMove={bestMove} />

        {/* 2. Analysis Settings */}
        <Paper sx={{ p: 3, bgcolor: '#262421', border: '1px solid #383531' }}>
          <Typography variant="subtitle2" sx={{ color: '#909090', mb: 2, textTransform: 'uppercase' }}>
            Engine Perspective
          </Typography>
          
          <ToggleButtonGroup
            value={analyzeSide}
            exclusive
            onChange={(e, val) => val && setAnalyzeSide(val)}
            fullWidth
            sx={{ 
              '& .MuiToggleButton-root': { 
                color: '#909090', 
                borderColor: '#383531',
                '&.Mui-selected': { color: '#fff', bgcolor: '#2C75FF', '&:hover': { bgcolor: '#1a60e0' } } 
              } 
            }}
          >
            <ToggleButton value="white">White</ToggleButton>
            <ToggleButton value="both">Both</ToggleButton>
            <ToggleButton value="black">Black</ToggleButton>
          </ToggleButtonGroup>
        </Paper>

        {/* 3. FEN String */}
        <Paper sx={{ p: 3, bgcolor: '#262421', border: '1px solid #383531' }}>
           <Typography variant="subtitle2" sx={{ color: '#909090', mb: 1 }}>Current FEN</Typography>
           <div className="bg-[#1E1D1B] p-2 rounded border border-[#383531] break-all text-xs font-mono text-[#2C75FF]">
             {game.fen()}
           </div>
           <Button 
             size="small" 
             sx={{ mt: 1, color: '#909090' }}
             onClick={() => navigator.clipboard.writeText(game.fen())}
             startIcon={<Icon icon="material-symbols:content-copy-outline" />}
           >
             Copy FEN
           </Button>
        </Paper>
      </div>
    </div>
  );
};

export default Analysis;