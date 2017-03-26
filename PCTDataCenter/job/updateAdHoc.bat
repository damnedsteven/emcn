FOR /L %%A IN (24,1,28) DO (
  curl http://16.187.224.112:8080/Yi/PCTDataCenter/updater.php?days=%%A
  timeout /t 3 /nobreak
)