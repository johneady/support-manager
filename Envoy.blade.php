@servers(['your-server-name.com' => 'user@your-server-ip -p 22'])

@setup
    /*
    * Load server configuration from envoy-config.php.
    * Add or update server entries in that file before running any tasks.
    */
    require __DIR__ . '/envoy-config.php';

    /*
    * Resolve the target server and extract its configuration variables.
    * Pass --server=<hostname> when invoking Envoy to select a server.
        */
        $server = $server ?? 'unknown';

        if (! isset($servers[$server])) {
        throw new Exception("Unknown server: {$server}");
        }

        $webroot = $servers[$server]['webroot'];
        $env = $servers[$server]['env'];
        $folder = $servers[$server]['folder'];
        $app_url = $servers[$server]['app_url'];
        $db_database = $servers[$server]['db_database'];
        $db_username = $servers[$server]['db_username'];
        $db_password = $servers[$server]['db_password'];
        $path = $webroot . '/' . $folder;
        $backups = $webroot . '/backups';
    @endsetup

    @story('update', ['on' => $server])
        backup
        pull-and-deploy
    @endstory

    @task('install', ['on' => $server, 'confirm' => true])
        echo "{{ $server }} is about to be installed."
        cd {{ $webroot }}

        rm -rf {{ $folder }}
        echo "Removed existing {{ $folder }} directory."

        git clone https://github.com/johneady/support-manager.git

        cd {{ $folder }}
        echo "Inside {{ $folder }} directory."

        composer install --optimize-autoloader

        cp {{ $env }} .env

        # Replace database credentials and app URL using PHP
        php -r "
        \$envFile = file_get_contents('.env');
        \$envFile = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE={{ $db_database }}', \$envFile);
        \$envFile = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME={{ $db_username }}', \$envFile);
        \$envFile = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD={{ $db_password }}', \$envFile);
        \$envFile = preg_replace('/APP_URL=.*/', 'APP_URL={{ $app_url }}', \$envFile);
        file_put_contents('.env', \$envFile);
        "

        # Verify the replacements
        echo "Database configuration:"
        grep "^DB_" .env
        echo "Application URL:"
        grep "^APP_URL" .env

        # Generate application key
        php artisan key:generate
        echo "Application key generated."

        php artisan storage:link

        php artisan migrate:fresh --force

        npm install
        npm run build

        rm -rf node_modules/

        echo "{{ $server }} has been installed."
        echo "1. Create a cron job that triggers this command every minute to start the scheduler:"
        echo "php {{ $path }}/cron_error.log"
    @endtask

    @task('backup', ['on' => $server])
        BACKUP_DIR={{ $backups }}
        mkdir -p $BACKUP_DIR

        TIMESTAMP=$(date +%Y%m%d_%H%M%S)
        FILES_BACKUP=$BACKUP_DIR/files_${TIMESTAMP}.tar.gz
        DB_BACKUP=$BACKUP_DIR/db_${TIMESTAMP}.sql

        echo "Backing up files to $FILES_BACKUP..."
        tar -czf $FILES_BACKUP -C {{ $webroot }} {{ $folder }}
        echo "File backup complete."

        DB_NAME=$(grep
        "^DB_DATABASE=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
    DB_USER=$(grep "^DB_USERNAME="
        {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
        DB_PASS=$(grep "^DB_PASSWORD=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')

    echo "Backing up database
        $DB_NAME to $DB_BACKUP..."
        mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > $DB_BACKUP
        echo "Database backup complete."

        echo "FILES=$FILES_BACKUP" > $BACKUP_DIR/last_backup_info
        echo "DB=$DB_BACKUP" >> $BACKUP_DIR/last_backup_info

        echo "Backup complete. Files: $FILES_BACKUP | DB: $DB_BACKUP"

        echo "Pruning old backups, keeping the last 1..."
        ls -t $BACKUP_DIR/files_*.tar.gz 2>/dev/null | tail -n +2 | xargs -r rm --
        ls -t $BACKUP_DIR/db_*.sql 2>/dev/null | tail -n +2 | xargs -r rm --
        echo "Old backups pruned."
    @endtask

    @task('pull-and-deploy', ['on' => $server])
        echo "{{ $server }} is about to be updated."
        cd {{ $path }}

        php artisan down
        echo "Maintenance mode enabled."

        rm -rf vendor/
        echo "Removed vendor/ directory."

        git pull origin main

        composer install --optimize-autoloader --no-dev

        php artisan migrate --force

        npm install
        npm run build

        rm -rf node_modules/
        echo "Removed node_modules/ directory."

        php artisan optimize:clear
        php artisan optimize
        php artisan up
        echo "Maintenance mode disabled."

        echo "{{ $server }} has been updated. Please check the application to ensure everything is working
        correctly."
    @endtask

    @task('restore', ['on' => $server, 'confirm' => true])
        if [ ! -f {{ $backups }}/last_backup_info ]; then
        echo "ERROR: No backup info found at {{ $backups }}/last_backup_info. Cannot restore."
        exit 1
        fi

        source {{ $backups }}/last_backup_info

        echo "Restoring files from $FILES..."
        tar -xzf $FILES -C {{ $webroot }}
        echo "File restore complete."

        DB_NAME=$(grep "^DB_DATABASE=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
        DB_USER=$(grep "^DB_USERNAME=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
        DB_PASS=$(grep "^DB_PASSWORD=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')

        echo "Restoring database $DB_NAME from $DB..."
        mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < $DB cd {{ $path }} php artisan config:clear php artisan
            optimize php artisan up echo "Maintenance mode disabled."
            echo "Restore complete. The application has been rolled back to the pre-update state."
            @endtask @error if ($task==='pull-and-deploy' ) { $lines=[ '' , '!!! DEPLOY FAILED — task: '
            . $task, '    A backup was taken before the update.' , '    To restore, run:'
            , '    vendor/bin/envoy run restore --server={{ $server }}' , '' , ]; foreach ($lines as $line) { echo
        $line . PHP_EOL; } } @enderror
