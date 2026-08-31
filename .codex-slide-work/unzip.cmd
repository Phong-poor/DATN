@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0unzip.ps1" -Mode "%~1" -Archive "%~2" -Entry "%~3"
