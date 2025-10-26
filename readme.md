## Invision Community 5 Cronjob Time Formatter

### This script will add the cronjob time to all IC5 tasks.



```diff

/**
 * advertStats Task
+ * Task frequency: Every day
 */
class advertStats extends Task
{
```


## Installation
Clone the repository into your Invision Community 5 directory.

## Usage
run the script
```
php cronjobtimeformatter.php
```

This can also be used with [File Watchers](https://www.jetbrains.com/help/phpstorm/using-file-watchers.html) to rebuild the files automatically once an applications/*/data/tasks.json file changes.