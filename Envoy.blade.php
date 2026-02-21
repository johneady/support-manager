@servers(['your-server-name.com' => 'user@your-server-ip -p 22', 'localhost' => '127.0.0.1'])

@setup
    $servers = [
    'your-server-name.com' => [
    'path' => '/path/to/your/support-manager',
    'env' => '.env.production',
    'folder' => 'support-manager',
    ],
    ];

    $server = $server ?? 'unknown';

    if (! isset($servers[$server])) {
    throw new Exception("Unknown server: {$server}");
    }

    $path = $servers[$server]['path'];
    $env = $servers[$server]['env'];
    $folder = $servers[$server]['folder'];
    $backups = dirname($path) . '/backups';
@endsetup

@story('update', ['on' => $server])
    backup
    pull-and-deploy
@endstory

@task('install', ['on' => $server, 'confirm' => true])
    echo "{{ $server }} is about to be installed."
    cd {{ dirname($path) }}

    rm -rf {{ $folder }}
    echo "Removed existing {{ $folder }} directory."

    git clone https://github.com/johneady/support-manager.git

    cd {{ $folder }}
    echo "Inside {{ $folder }} directory."

    composer install --optimize-autoloader

    cp {{ $env }} .env

    php artisan storage:link

    php artisan config:clear
    php artisan optimize

    php artisan migrate:fresh --force
    php artisan db:seed --force

    npm install
    npm run build

    rm -rf node_modules/

    echo "{{ $server }} has been installed."
    echo "1. Create a cron job that triggers this command every minute to start the scheduler:"
    echo "php {{ $path }}/artisan schedule:run 1> /dev/null 2> {{ dirname($path) }}/../cron_error.log"
@endtask

@task('backup', ['on' => $server])
    BACKUP_DIR={{ $backups }}
    mkdir -p $BACKUP_DIR

    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    FILES_BACKUP=$BACKUP_DIR/files_${TIMESTAMP}.tar.gz
    DB_BACKUP=$BACKUP_DIR/db_${TIMESTAMP}.sql

    echo "Backing up files to $FILES_BACKUP..."
    tar -czf $FILES_BACKUP -C {{ dirname($path) }} {{ $folder }}
    echo "File backup complete."

    DB_NAME=$(grep "^DB_DATABASE=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
    DB_USER=$(grep "^DB_USERNAME=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
    DB_PASS=$(grep "^DB_PASSWORD=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')

    echo "Backing up database $DB_NAME to $DB_BACKUP..."
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

    cp {{ $env }} .env

    php artisan migrate --force
    php artisan config:clear
    php artisan optimize

    npm install

    npm run build

    rm -rf node_modules/
    echo "Removed node_modules/ directory."

    php artisan up
    echo "Maintenance mode disabled."

    echo "{{ $server }} has been updated. Please check the application to ensure everything is working correctly."
@endtask

@task('restore', ['on' => $server, 'confirm' => true])
    if [ ! -f {{ $backups }}/last_backup_info ]; then
        echo "ERROR: No backup info found at {{ $backups }}/last_backup_info. Cannot restore."
        exit 1
    fi

    source {{ $backups }}/last_backup_info

    echo "Restoring files from $FILES..."
    tar -xzf $FILES -C {{ dirname($path) }}
    echo "File restore complete."

    DB_NAME=$(grep "^DB_DATABASE=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
    DB_USER=$(grep "^DB_USERNAME=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')
    DB_PASS=$(grep "^DB_PASSWORD=" {{ $path }}/.env | cut -d= -f2 | tr -d '\r')

    echo "Restoring database $DB_NAME from $DB..."
    mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < $DB

    cd {{ $path }}

    php artisan config:clear
    php artisan optimize

    php artisan up
    echo "Maintenance mode disabled."

    echo "Restore complete. The application has been rolled back to the pre-update state."
@endtask

@error
    if ($task === 'pull-and-deploy') {
        $lines = [
            '',
            '!!! DEPLOY FAILED — task: ' . $task,
            '    A backup was taken before the update.',
            '    To restore, run:',
            '    vendor/bin/envoy run restore --server={{ $server }}',
            '',
        ];
        foreach ($lines as $line) {
            echo $line . PHP_EOL;
        }
    }
@enderror
