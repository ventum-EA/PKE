@echo off
chcp 65001 >nul 2>nul
title Chess Platform - Setup

echo.
echo  ================================================
echo    Saha Analizes Platforma - Windows Setup
echo  ================================================
echo.

REM -- Check Docker is running --
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo  [!] Docker Desktop is not running.
    echo      Please start Docker Desktop and try again.
    echo.
    echo      Download: https://www.docker.com/products/docker-desktop/
    echo.
    pause
    exit /b 1
)
echo  [OK] Docker Desktop is running

REM -- Build and start --
echo.
echo  [1/3] Building containers - first time takes 5-10 min...
docker compose -f docker-compose.standalone.yml up --build -d
if %errorlevel% neq 0 (
    echo  [!] Build failed. Check the output above.
    pause
    exit /b 1
)

REM -- Wait for the app --
echo  [2/3] Waiting for the platform to start...
timeout /t 20 /nobreak >nul

REM -- Health check --
echo  [3/3] Checking health...
curl -s -o nul http://localhost >nul 2>&1
if %errorlevel% neq 0 (
    echo  Still starting... waiting 30 more seconds...
    timeout /t 30 /nobreak >nul
)

echo.
echo  ================================================
echo    Ready!
echo.
echo    Platform:  http://localhost
echo    Mailpit:   http://localhost:8025
echo.
echo    Demo account:
echo      Email:  admin@chess.local
echo      Pass:   password
echo.
echo    Or register a new account at /register
echo  ================================================
echo.
echo  To stop:  docker compose -f docker-compose.standalone.yml down
echo  To reset: docker compose -f docker-compose.standalone.yml down -v
echo.

REM -- Open in browser --
start http://localhost

pause
