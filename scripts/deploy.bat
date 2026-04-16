@echo off
REM Lokální deploy skript pro Rajón
REM Použití: scripts\deploy.bat [full]
REM   bez parametru = smart deploy (jen git diff)
REM   full = kompletní nahrání všech souborů

cd /d "%~dp0\.."

echo === Rajón Deploy ===
echo.

REM Build assets
echo [1/4] Instalace závislostí...
call composer install --no-dev --optimize-autoloader --no-interaction 2>nul
call npm install 2>nul
call npm run build 2>nul

echo [2/4] Nahrávání app souborů...
echo.

if "%1"=="full" (
    echo FULL DEPLOY — nahrávám vše
    lftp -e "set sftp:connect-program 'ssh -o StrictHostKeyChecking=no -p 222'; set xfer:clobber on; set net:timeout 30; open sftp://multi_833363@ftp.tuptudu.cz; mirror --reverse --no-perms --exclude storage/logs/ --exclude storage/framework/sessions/ --exclude node_modules/ --exclude .git/ --exclude .claude/ --exclude public/ --parallel=3 --verbose . /tuptudu.cz/rajon/; quit"
) else (
    echo SMART DEPLOY — nahrávám změněné soubory
    REM Toto vyžaduje lftp nainstalovaný lokálně
    echo Použij WinSCP nebo Total Commander pro ruční upload
)

echo.
echo [3/4] Nahrávání public souborů...
if "%1"=="full" (
    lftp -e "set sftp:connect-program 'ssh -o StrictHostKeyChecking=no -p 222'; set xfer:clobber on; set net:timeout 30; open sftp://multi_833363@ftp.tuptudu.cz; mirror --reverse --no-perms --parallel=3 --verbose public/ /tuptudu.cz/_sub/rajon/; quit"
)

echo.
echo [4/4] Post-deploy hook...
curl -sL --max-time 60 "https://rajon.tuptudu.cz/deploy-hook.php?token=ARb1jyk9PdAE06mxnTAaL6CHEzCBlgF4wTzesltW&migrate"

echo.
echo === Deploy complete ===
pause
