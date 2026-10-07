@echo off
setlocal

echo Verifica versione PHP...
php -v || goto :error

echo.
echo Verifica driver PostgreSQL di PHP...
php --ri pdo_pgsql >nul 2>&1
if errorlevel 1 goto :missing_driver

php --ri pgsql >nul 2>&1
if errorlevel 1 goto :missing_driver

echo PDO PostgreSQL: disponibile
echo PostgreSQL: disponibile

echo.
echo Verifica client PostgreSQL...
psql --version || goto :missing_psql

echo.
echo Ambiente pronto per la migrazione PostgreSQL.
exit /b 0

:missing_driver
echo ERRORE: abilitare pdo_pgsql e pgsql nel php.ini usato da Apache.
exit /b 1

:missing_psql
echo ERRORE: psql non e disponibile nel PATH di Windows.
exit /b 1

:error
echo ERRORE: PHP non e disponibile nel PATH di Windows.
exit /b 1
