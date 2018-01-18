FOR /L %%A IN (0,1,50) DO (
  curl http://16.187.229.14/emcn/PCTDataCenter/updater_OnlineNo.php?days=%%A
  timeout /t 3 /nobreak
)