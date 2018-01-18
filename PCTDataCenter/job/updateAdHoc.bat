FOR /L %%A IN (24,1,28) DO (
  curl http://16.187.229.14/emcn/PCTDataCenter/updater.php?days=%%A
  timeout /t 3 /nobreak
)