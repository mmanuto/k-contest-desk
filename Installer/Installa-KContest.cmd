@echo off
setlocal

set "SCRIPT=%~dp0scripts\install-kcontest.ps1"

if not exist "%SCRIPT%" (
    echo ERRORE: script non trovato:
    echo %SCRIPT%
    pause
    exit /b 1
)

powershell.exe -NoProfile -ExecutionPolicy Bypass ^
    -Command "Start-Process powershell.exe -Verb RunAs -Wait -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File ""%SCRIPT%""'"

if errorlevel 1 (
    echo.
    echo Installazione terminata con errore o annullata.
    pause
    exit /b 1
)

echo.
echo Installazione completata.
pause