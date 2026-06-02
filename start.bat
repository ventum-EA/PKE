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
"%ProgramFiles%\Docker\Docker\resources\bin\docker-compose.exe" version >nul 2>&1
if %errorlevel% equ 0 (
    set "DC=%ProgramFiles%\Docker\Docker\resources\bin\docker-compose.exe"
    goto :found_compose
)
echo  [!] Could not find docker compose.
pause
exit /b 1

:found_compose
echo  [OK] Using: %DC%
echo.
echo  [1/2] Building and starting containers...
%DC% -f docker-compose.standalone.yml up --build -d
if %errorlevel% neq 0 goto :build_failed

echo  [2/2] Waiting for platform to start...
timeout /t 30 /nobreak >nul

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
start http://localhost
pause
exit /b 0

:no_docker
echo  [!] Docker Desktop is not running.
pause
exit /b 1

:build_failed
echo  [!] Build failed. Check output above.
pause
exit /b 1
