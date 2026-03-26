#!/bin/bash
# install-stockfish.sh — Run inside the Sail container
# Usage: ./vendor/bin/sail shell < docker/install-stockfish.sh
#   or:  ./vendor/bin/sail root-shell -c "bash /var/www/html/docker/install-stockfish.sh"

set -e

echo "=== Installing Stockfish chess engine ==="

apt-get update -qq
apt-get install -y --no-install-recommends stockfish

# Verify installation
if command -v stockfish &> /dev/null; then
    echo "✓ Stockfish installed at $(which stockfish)"
    stockfish <<< "uci" 2>/dev/null | head -5
    echo "✓ Engine ready for server-side analysis"
else
    echo "✕ Stockfish installation failed"
    exit 1
fi

echo ""
echo "=== Done ==="
echo "Server-side analysis is now available via AnalyzeGameJob."
echo "Browser WASM analysis works independently (no install needed)."
