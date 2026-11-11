
**************************************************************************

DEPLOYMENT STEPS

- log in as admin

- Put site in maintenance mode - https://cloudeats.app/?app=admin&p=maintenance

- Verify on phone that site is in Maintenance Mode

- commit to <main> branch (Trigger deployment)

- Log in and run migrations - https://cloudeats.app/?app=admin&p=migrations

- Import/Restore data (json file) - https://cloudeats.app/?app=admin&p=restore_db

- Verify site functionality

- Disable Site Maintenance mode




------------------------------------------------------------------
Git Merge Commands 

# 1. Switch to the main branch
git checkout main

# 2. Optional: Pull the latest changes from the remote server to ensure main is up to date
git pull origin main

# 3. Merge the <branch-name> branch into main
git merge <branch-name>



**************************************************************************
