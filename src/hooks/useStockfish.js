import { useEffect, useState, useRef } from 'react';

export function useStockfish() {
  const [evaluation, setEvaluation] = useState(null);
  const [bestMove, setBestMove] = useState(null);
  const engine = useRef(null);

  useEffect(() => {
    engine.current = new Worker('/stockfish.js');
    engine.current.postMessage('uci');
    engine.current.onmessage = (event) => {
      const msg = event.data;
      if (msg.startsWith('info depth') && msg.includes('score cp')) {
        const score = msg.match(/score cp (-?\d+)/);
        if (score) setEvaluation((parseInt(score[1]) / 100).toFixed(2));
      }
      if (msg.startsWith('bestmove')) {
        setBestMove(msg.split(' ')[1]);
      }
    };
    return () => engine.current.terminate();
  }, []);

  const analyze = (fen) => {
    if (engine.current) {
      engine.current.postMessage('stop');
      engine.current.postMessage(`position fen ${fen}`);
      engine.current.postMessage('go depth 15');
    }
  };

  return { evaluation, bestMove, analyze };
}
