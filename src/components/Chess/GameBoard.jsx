import React from 'react';
import { Chessboard } from 'react-chessboard';
import { Box } from '@mui/material';

const GameBoard = ({ 
  game, 
  onPieceDrop, 
  orientation = 'white', 
  highlightSquares = {} 
}) => {
  
  // Combine custom highlights (e.g., for errors) with default move styling if needed
  const customSquareStyles = {
    ...highlightSquares,
  };

  return (
    <Box 
      sx={{ 
        width: '100%', 
        maxWidth: '70vh', // Keeps board responsive but not too huge
        aspectRatio: '1/1',
        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
        borderRadius: '8px',
        overflow: 'hidden'
      }}
    >
      <Chessboard 
        id="PlayVsStockfish"
        position={game.fen()} 
        onPieceDrop={onPieceDrop}
        boardOrientation={orientation}
        customBoardStyle={{
          borderRadius: '4px',
        }}
        // Visual customization
        customDarkSquareStyle={{ backgroundColor: '#779954' }}
        customLightSquareStyle={{ backgroundColor: '#e9edcc' }}
        customSquareStyles={customSquareStyles}
        animationDuration={200}
      />
    </Box>
  );
};

export default GameBoard;