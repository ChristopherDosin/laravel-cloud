
set -e

# Set Variables

BACKUP="{!! basename($backup->backup_path) !!}"
UNZIPPED_BACKUP="{!! basename($backup->backup_path, '.gz') !!}"

# Create Restore Directory

mkdir -p /home/cloud/restores

# Create Provider Credentials File

{!! $backup->configurationScript() !!}

# Download The Backup From The Provider

rm -f /home/cloud/restores/${BACKUP}
rm -f /home/cloud/restores/${UNZIPPED_BACKUP}

{!! $backup->downloadScript() !!}

# Test Result Of Download

if [ "$?" -ne "0" ]; then
    echo "Failed to download backup from storage provider."

    exit 1
fi

gunzip /home/cloud/restores/${BACKUP}

# Create The Database

mysql --user="cloud" --password="{!! $backup->database->password !!}" -e "CREATE DATABASE IF NOT EXISTS {!! $backup->database_name !!} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

mysql -u cloud -p{!! $backup->database->password !!} {!! $backup->database_name !!} < /home/cloud/restores/${UNZIPPED_BACKUP}

# Remove The Restore Files

rm -f /home/cloud/restores/${BACKUP}
rm -f /home/cloud/restores/${UNZIPPED_BACKUP}
