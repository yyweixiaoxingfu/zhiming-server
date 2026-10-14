@echo off
CHCP 65001

:: IM GatewayWorker 启动脚本
:: 支持的命令: start, stop, restart, status

:: 检查PHP是否存在
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: PHP is not found, please add PHP to environment variables
    pause
    exit /b 1
)

:: 定义项目根目录
set PROJECT_ROOT=%~dp0..

:: 处理命令行参数
set ACTION=start
if "%1" NEQ "" (
    set ACTION=%1
)

:: 执行相应操作
if "%ACTION%" == "start" (
    echo Starting IM GatewayWorker...
    php "%PROJECT_ROOT%\application\im\gateway\start.php" start
    echo.
    echo IM GatewayWorker started successfully!
) else if "%ACTION%" == "stop" (
    echo Stopping IM GatewayWorker...
    php "%PROJECT_ROOT%\application\im\gateway\start.php" stop
    echo.
    echo IM GatewayWorker stopped!
) else if "%ACTION%" == "restart" (
    echo Restarting IM GatewayWorker...
    php "%PROJECT_ROOT%\application\im\gateway\start.php" restart
    echo.
    echo IM GatewayWorker restarted successfully!
) else if "%ACTION%" == "status" (
    echo Checking IM GatewayWorker status...
    echo.
    php "%PROJECT_ROOT%\application\im\gateway\start.php" status
) else (
    echo Invalid command!
    echo Usage: %~nx0 [start|stop|restart|status]
    echo Example: %~nx0 start
)

pause