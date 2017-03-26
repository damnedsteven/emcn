FOR /L %%A IN (0,1,50) DO (
  curl http://16.187.224.112:8080/Yi/PCTDataCenter/updater_HO.php?days=%%A
  timeout /t 3 /nobreak
)