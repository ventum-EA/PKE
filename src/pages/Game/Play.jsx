import React, { useState, useEffect } from "react";
import { Chess } from "chess.js";
import { Button, TextField, Tabs, Tab } from "@mui/material";
import { useStockfish } from "../../hooks/useStockfish";
import GameBoard from "../../components/Chess/GameBoard";
import AnalysisPanel from "../../components/Chess/AnalysisPanel";
import MoveHistory from "../../components/Chess/MoveHistory";

const Play = () => {
  const [game, setGame] = useState(new Chess());
  const [tabIndex, setTabIndex] = useState(0); // 0 = Play, 1 = Analysis
  const [pgn, setPgn] = useState("");
  
  // Custom hook to interact with the Stockfish Worker
  const { evaluation, bestMove, analyze } = useStockfish();

  // Trigger analysis whenever the game state changes
  useEffect(() => {
    analyze(game.fen());
  }, [game]);

  // Handle piece movement
  const onDrop = (source, target) => {
    try {
      const move = game.move({ from: source, to: target, promotion: "q" });
      if (!move) return false; // Illegal move
      setGame(new Chess(game.fen())); // Update state to trigger re-render
      return true;
    } catch (e) { 
      return false; 
    }
  };

  // Handle loading a game from PGN text
  const handlePgnLoad = () => {
    try {
      const newGame = new Chess();
      newGame.loadPgn(pgn);
      setGame(newGame);
    } catch (e) { 
      alert("Invalid PGN format"); 
    }
  };

  // Reset the board
  const handleNewGame = () => {
    setGame(new Chess());
    setPgn("");
  };

  // Undo the last move
  const handleUndo = () => {
    game.undo();
    setGame(new Chess(game.fen()));
  };

  return (
    // Main Container: Centered vertically and horizontally, full height minus sidebar/header space
    <div className="flex flex-col xl:flex-row gap-8 items-center justify-center min-h-[calc(100vh-64px)] w-full py-8">
      
      {/* --- Left Column: The Chess Board --- */}
      <div className="flex-shrink-0 bg-[#262421] p-1 rounded-sm shadow-2xl border border-[#383531]">
        <GameBoard 
          game={game} 
          onPieceDrop={onDrop}
          // Custom Board Colors to match the Cherenkov Blue theme
          customDarkSquareStyle={{ backgroundColor: '#2C75FF' }} 
          customLightSquareStyle={{ backgroundColor: '#E1E1E1' }}
          highlightSquares={{}} // Add logic here later for visual error feedback
        />
        
        {/* Player Status Bars (Mock Data) */}
        <div className="mt-3 flex justify-between px-2 text-[#909090] font-semibold text-sm">
          <div className="flex items-center gap-2">
             <div className="w-2 h-2 bg-white rounded-full"></div>
             <span>Player (1200)</span>
          </div>
          <div className="flex items-center gap-2">
             <span>Stockfish 16</span>
             <div className="w-2 h-2 bg-black border border-gray-600 rounded-full"></div>
          </div>
        </div>
      </div>

      {/* --- Right Column: Controls, Analysis & History --- */}
      <div className="w-full max-w-[450px] flex flex-col gap-4 h-[650px]">
        
        {/* 1. Tab Switcher (Play vs Analyze) */}
        <div className="bg-[#211F1C] rounded-sm border border-[#383531] p-1">
          <Tabs 
            value={tabIndex} 
            onChange={(e, v) => setTabIndex(v)} 
            variant="fullWidth" 
            textColor="primary"
            indicatorColor="primary"
            sx={{ minHeight: '40px' }}
          >
            <Tab label="Play" sx={{ color: '#909090', fontWeight: 600, minHeight: '40px' }} />
            <Tab label="Analyze" sx={{ color: '#909090', fontWeight: 600, minHeight: '40px' }} />
          </Tabs>
        </div>

        {/* 2. Dynamic Panel: Shows Analysis Engine if tab is selected */}
        {tabIndex === 1 && (
          <div className="animate-fade-in shadow-lg">
            <AnalysisPanel evaluation={evaluation} bestMove={bestMove} />
          </div>
        )}

        {/* 3. Move History List (Fills remaining vertical space) */}
        <div className="flex-grow bg-[#262421] rounded-sm border border-[#383531] overflow-hidden flex flex-col shadow-inner">
          <MoveHistory history={game.history()} />
        </div>

        {/* 4. Action Controls (Undo, New Game, PGN) */}
        <div className="bg-[#262421] p-4 rounded-sm border border-[#383531] flex flex-col gap-3 shadow-lg">
          <div className="flex gap-3">
            <Button 
              variant="outlined" 
              fullWidth 
              onClick={handleUndo}
              sx={{ 
                borderColor: '#45413e', 
                color: '#C3C3C3', 
                borderWidth: '1px',
                '&:hover': { borderColor: '#2C75FF', color: 'white', backgroundColor: 'rgba(44,117,255,0.05)' } 
              }}
            >
              Undo
            </Button>
            <Button 
              variant="contained" 
              fullWidth 
              onClick={handleNewGame}
              sx={{ 
                fontWeight: 'bold',
                boxShadow: 'none',
                '&:hover': { boxShadow: '0 4px 12px rgba(44, 117, 255, 0.2)' }
              }}
            >
              New Game
            </Button>
          </div>
          
          {/* PGN Loader Section */}
          <div className="pt-3 border-t border-[#383531]">
             <TextField 
              size="small" 
              fullWidth 
              placeholder="Paste PGN here..." 
              value={pgn} 
              onChange={(e) => setPgn(e.target.value)} 
              InputProps={{ 
                style: { fontSize: 13, color: '#C3C3C3', backgroundColor: '#1e1d1b' } 
              }}
              sx={{
                '& .MuiOutlinedInput-root': {
                  '& fieldset': { borderColor: '#383531' },
                  '&:hover fieldset': { borderColor: '#555' },
                  '&.Mui-focused fieldset': { borderColor: '#2C75FF' },
                },
              }}
            />
            <Button 
              size="small" 
              fullWidth 
              className="mt-2" 
              onClick={handlePgnLoad}
              sx={{ color: '#2C75FF', marginTop: '8px', fontWeight: 600 }}
            >
              Load PGN
            </Button>
          </div>
        </div>
      </div>

    </div>
  );
};

export default Play;