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
if %errorlevel% neq 0 goto :no_docker

echo  [OK] Docker Desktop is running

REM -- Detect compose command --
docker compose version >nul 2>&1
if %errorlevel% equ 0 (
    set DC=docker compose
    goto :found_compose
)

docker-compose version >nul 2>&1
if %errorlevel% equ 0 (
    set DC=docker-compose
    goto :found_compose
)

REM -- Neither worked, try the explicit plugin path --
"%ProgramFiles%\Docker\Docker\resources\bin\docker-compose.exe" version >nul 2>&1
if %errorlevel% equ 0 (
    set "DC=%ProgramFiles%\Docker\Docker\resources\bin\docker-compose.exe"
    goto :found_compose
)

echo  [!] Could not find docker compose.
echo      Try running manually in PowerShell:
echo        docker-compose -f docker-compose.standalone.yml up --build -d
pause
exit /b 1

:found_compose
echo  [OK] Using: %DC%
echo.
echo  [1/3] Building containers - first time takes 5-10 min...
%DC% -f docker-compose.standalone.yml up --build -d
if %errorlevel% neq 0 goto :build_failed

echo  [2/3] Waiting for the platform to start...
timeout /t 20 /nobreak >nul

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
echo  ================================================
echo.
echo  To stop:  %DC% -f docker-compose.standalone.yml down
echo  To reset: %DC% -f docker-compose.standalone.yml down -v
echo.
start http://localhost
pause
exit /b 0

:no_docker
echo  [!] Docker Desktop is not running.
echo      Please start Docker Desktop and try again.
pause
exit /b 1

:build_failed
echo  [!] Build failed. Check the output above.
pause
exit /b 1
