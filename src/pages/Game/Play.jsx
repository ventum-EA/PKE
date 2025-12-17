import React, { useState, useEffect } from "react";
import { Chess } from "chess.js";
import { 
  Paper, Button, TextField, Tabs, Tab, Box, IconButton 
} from "@mui/material";
import { Icon } from "@iconify/react";
import { useStockfish } from "../../hooks/useStockfish";
import { soundManager } from "../../utils/SoundManager"; // Ensure you created this utility
import GameBoard from "../../components/Chess/GameBoard";
import AnalysisPanel from "../../components/Chess/AnalysisPanel";
import MoveHistory from "../../components/Chess/MoveHistory";

const Play = () => {
  const [game, setGame] = useState(new Chess());
  const [tabIndex, setTabIndex] = useState(0); 
  const [pgn, setPgn] = useState("");
  
  const { evaluation, bestMove, analyze } = useStockfish();

  // Analyze on every move
  useEffect(() => {
    analyze(game.fen());
  }, [game]);

  // Handle Piece Drop
  const onDrop = (source, target) => {
    try {
      const move = game.move({ from: source, to: target, promotion: "q" });
      if (!move) return false;
      
      // FIX: Play sound only on user interaction
      if (move.captured) {
        soundManager.play('capture');
      } else {
        soundManager.play('move');
      }

      setGame(new Chess(game.fen()));
      return true;
    } catch (e) { 
      return false; 
    }
  };

  const handlePgnLoad = () => {
    try {
      const newGame = new Chess();
      newGame.loadPgn(pgn);
      setGame(newGame);
      soundManager.play('notify');
    } catch (e) { 
      alert("Invalid PGN format"); 
    }
  };

  const handleNewGame = () => {
    setGame(new Chess());
    setPgn("");
    soundManager.play('notify');
  };

  const handleUndo = () => {
    game.undo();
    setGame(new Chess(game.fen()));
  };

  return (
    <div className="flex flex-col xl:flex-row gap-6 h-[calc(100vh-140px)] animate-fade-in">
      
      {/* LEFT: Chess Board Area */}
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
          overflow: 'hidden',
          p: 2
        }}
      >
        <div className="w-full max-w-[70vh] aspect-square">
          <GameBoard 
            game={game} 
            onPieceDrop={onDrop}
            customDarkSquareStyle={{ backgroundColor: '#2C75FF' }} 
            customLightSquareStyle={{ backgroundColor: '#E1E1E1' }}
          />
        </div>

        {/* Player Info Overlays could go here */}
        <div className="absolute top-4 left-4 bg-black/50 px-3 py-1 rounded text-white text-sm backdrop-blur-sm">
           Stockfish 16 (Level 20)
        </div>
      </Paper>

      {/* RIGHT: Controls & Tools */}
      <div className="w-full xl:w-[400px] flex flex-col gap-4">
        
        {/* Tabs */}
        <Paper sx={{ bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2 }}>
          <Tabs 
            value={tabIndex} 
            onChange={(e, v) => setTabIndex(v)} 
            variant="fullWidth" 
            textColor="primary"
            indicatorColor="primary"
            sx={{ minHeight: '48px' }}
          >
            <Tab label="Play" sx={{ color: '#909090', fontWeight: 600 }} />
            <Tab label="Analyze" sx={{ color: '#909090', fontWeight: 600 }} />
          </Tabs>
        </Paper>

        {/* Analysis Panel (Conditional) */}
        {tabIndex === 1 && (
          <AnalysisPanel evaluation={evaluation} bestMove={bestMove} />
        )}

        {/* Move History - Fills remaining space */}
        <div className="flex-grow bg-[#262421] rounded-lg border border-[#383531] overflow-hidden flex flex-col">
          <MoveHistory history={game.history()} />
        </div>

        {/* Game Controls */}
        <Paper sx={{ p: 3, bgcolor: '#262421', border: '1px solid #383531', borderRadius: 2 }}>
          <div className="flex gap-3 mb-4">
            <Button 
              variant="outlined" 
              fullWidth 
              startIcon={<Icon icon="material-symbols:undo" />}
              onClick={handleUndo}
              sx={{ borderColor: '#45413e', color: '#C3C3C3', '&:hover': { borderColor: '#2C75FF', color: 'white' } }}
            >
              Undo
            </Button>
            <Button 
              variant="contained" 
              fullWidth 
              startIcon={<Icon icon="material-symbols:add-circle-outline" />}
              onClick={handleNewGame}
              sx={{ fontWeight: 'bold' }}
            >
              New Game
            </Button>
          </div>
          
          {/* PGN Input */}
          <Box sx={{ display: 'flex', gap: 1 }}>
             <TextField 
              size="small" 
              fullWidth 
              placeholder="Paste PGN..." 
              value={pgn} 
              onChange={(e) => setPgn(e.target.value)} 
              InputProps={{ 
                style: { fontSize: 13, color: '#C3C3C3', backgroundColor: '#1e1d1b' } 
              }}
              sx={{ '& fieldset': { borderColor: '#383531' } }}
            />
            <IconButton 
              onClick={handlePgnLoad}
              sx={{ 
                bgcolor: '#2C75FF', 
                color: 'white', 
                borderRadius: 1, 
                '&:hover': { bgcolor: '#1a60e0' } 
              }}
            >
              <Icon icon="material-symbols:download" />
            </IconButton>
          </Box>
        </Paper>
      </div>
    </div>
  );
};

export default Play;