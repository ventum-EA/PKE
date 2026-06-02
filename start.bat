@echo off
chcp 65001 >nul
title Šaha Analīzes Platforma — Setup

echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║   Šaha Analīzes Platforma — Windows Setup    ║
echo  ╚══════════════════════════════════════════════╝
echo.

:: ── Check Docker is running ──
docker info >nul 2>&1
if errorlevel 1 (
    echo  [!] Docker Desktop is not running.
    echo      Please start Docker Desktop and try again.
    echo.
    echo      If you don't have it installed:
    echo      https://www.docker.com/products/docker-desktop/
    echo.
    pause
    exit /b 1
)
echo  [OK] Docker Desktop is running

:: ── Build and start ──
echo.
echo  [1/3] Building containers (first time takes 5-10 min)...
docker compose -f docker-compose.standalone.yml up --build -d
if errorlevel 1 (
    echo  [!] Build failed. Check the output above.
    pause
    exit /b 1
)

:: ── Wait for the app to be ready ──
echo  [2/3] Waiting for the platform to start...
timeout /t 15 /nobreak >nul

:: ── Health check ──
echo  [3/3] Checking health...
curl -s -o nul -w "%%{http_code}" http://localhost >nul 2>&1
if errorlevel 1 (
    echo  [!] Platform not responding yet. Give it another 30 seconds...
    timeout /t 30 /nobreak >nul
)

echo.
echo  ╔══════════════════════════════════════════════╗
echo  ║              Ready!                          ║
echo  ║                                              ║
echo  ║   Platform:  http://localhost                 ║
echo  ║   Mailpit:   http://localhost:8025            ║
echo  ║                                              ║
echo  ║   Demo account:                              ║
echo  ║     Email:  admin@chess.local                 ║
echo  ║     Pass:   password                          ║
echo  ║                                              ║
echo  ║   Or register a new account at /register      ║
echo  ╚══════════════════════════════════════════════╝
echo.
echo  To stop:  docker compose -f docker-compose.standalone.yml down
echo  To reset: docker compose -f docker-compose.standalone.yml down -v
echo.

:: ── Open in browser ──
start http://localhost

pause
