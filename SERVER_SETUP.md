Server Configuration Guide

1. Set up the Git repository permissions:

cd /var/www/sync
sudo chown -R www-data:www-data .
sudo chmod -R 775 .


2. Configure Git for the repository:

cd /var/www/sync
git config --global --add safe.directory /var/www/sync
sudo git config --global --add safe.directory /var/www/sync


3. Create the dumps directory:

mkdir -p /var/www/sync/dumps
sudo chown www-data:www-data /var/www/sync/dumps
sudo chmod 775 /var/www/sync/dumps


4. Set up the .env file:

Copy .env.example to .env and fill in your credentials:
- Database names
- Database passwords
- GitHub token
- GitHub repository name
- Git email and name


5. Ensure PHP has the required extensions:

sudo apt-get install php-mysqli php-mbstring


6. Set proper permissions for the application directory:

cd /var/www/sync_database
sudo chown -R www-data:www-data .
sudo chmod -R 755 .


7. Create logs directory:

mkdir -p /var/www/sync_database/logs
sudo chown www-data:www-data /var/www/sync_database/logs
sudo chmod 775 /var/www/sync_database/logs


8. Test the setup:

Login to the application and try a sync operation. Check that:
- Database backups are created in /var/www/sync/dumps
- Changes are automatically pushed to GitHub
- No permission errors appear


That's it! The application is ready to use.
