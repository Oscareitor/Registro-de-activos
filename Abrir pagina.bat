@echo off
setlocal

cd /d "%~dp0"

where php >nul 2>&1
if errorlevel 1 (
    echo No se encontro PHP. Instala PHP o agregalo al PATH de Windows.
    pause
    exit /b 1
)

powershell -NoProfile -ExecutionPolicy Bypass -Command "$url='http://127.0.0.1:8000/app/seleccion_inicio/seleccion_inicio.html'; try { $response=Invoke-WebRequest $url -UseBasicParsing -TimeoutSec 2; $ready=$response.Content.Contains('btnAdministrador') } catch { $ready=$false }; if (-not $ready) { $listener=Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue; if ($listener) { throw 'El puerto 8000 esta ocupado por otro servicio.' }; Start-Process -FilePath 'php' -ArgumentList @('-S','127.0.0.1:8000','-t','%~dp0') -WindowStyle Hidden; $deadline=(Get-Date).AddSeconds(10); do { try { $response=Invoke-WebRequest $url -UseBasicParsing -TimeoutSec 2; $ready=$response.Content.Contains('btnAdministrador') } catch { $ready=$false }; if (-not $ready) { Start-Sleep -Milliseconds 250 } } while (-not $ready -and (Get-Date) -lt $deadline); if (-not $ready) { throw 'No se pudo iniciar el servidor PHP.' } }; Start-Process $url"
if errorlevel 1 (
    echo No se pudo abrir el sistema. Revisa PHP y el puerto 8000.
    pause
    exit /b 1
)