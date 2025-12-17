import React from 'react';

const AnalysisPanel = ({ evaluation, bestMove }) => {
  // Determine bar percentage and color
  let evalNum = parseFloat(evaluation) || 0;
  // Clamp between -5 and 5 for visual bar
  const percentage = Math.min(Math.max((evalNum + 5) * 10, 5), 95); 
  const isPositive = evalNum > 0;

  return (
    <div className="bg-[#262421] p-0 rounded-sm border border-[#383531] overflow-hidden">
      {/* Header */}
      <div className="bg-[#211F1C] px-4 py-2 border-b border-[#383531] flex justify-between items-center">
        <span className="text-xs font-bold uppercase tracking-widest text-[#909090]">Engine Depth 16</span>
        <div className="h-2 w-2 rounded-full bg-[#2C75FF] shadow-[0_0_8px_#2C75FF]"></div>
      </div>
      
      <div className="p-5">
        <div className="flex justify-between items-end mb-2">
          <span className="text-[#909090] text-sm font-medium">Evaluation</span>
          <span className={`text-3xl font-mono font-bold ${isPositive ? "text-[#2C75FF]" : "text-[#FF4D4D]"}`}>
            {evaluation || "0.00"}
          </span>
        </div>

        {/* Sharp Progress Bar */}
        <div className="h-2 w-full bg-[#1e1d1b] relative mb-6">
          <div 
            className={`absolute h-full transition-all duration-500 ${isPositive ? 'bg-[#2C75FF]' : 'bg-[#FF4D4D]'}`}
            style={{ width: `${percentage}%` }}
          ></div>
          {/* Center Marker */}
          <div className="absolute left-1/2 top-0 bottom-0 w-px bg-[#555]"></div>
        </div>

        <div className="bg-[#302E2B] p-3 border-l-2 border-[#2C75FF]">
          <span className="block text-xs text-[#909090] uppercase mb-1">Best Move</span>
          <span className="text-lg font-bold text-white tracking-wide">{bestMove || "Calculating..."}</span>
        </div>
      </div>
    </div>
  );
};

export default AnalysisPanel;