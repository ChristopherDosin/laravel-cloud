
set -e

# Set Variables

BACKUP="{!! basename($backup->backup_path) !!}"

# Create Backup Directory

mkdir -p /home/cloud/backups

# Create Provider Credentials File

{!! $backup->configurationScript() !!}

# Create Backup

mysqldump --single-transaction --skip-lock-tables --quick \
     -u cloud -p{!! $backup->database->password !!} \
     {!! $backup->database_name !!} | gzip > /home/cloud/backups/${BACKUP}

# Verify The Backup File Was Created

if [ ! -e /home/cloud/backups/${BACKUP} ]; then
    echo "The backup was not created."

    exit 1
fi

# Upload The Backup To The Provider

{!! $backup->uploadScript() !!}

# Test Result Of Upload

if [ "$?" -ne "0" ]; then
    echo "Failed to upload backup to storage provider."

    exit 1
fi

# Remove The Backup File

rm -f /home/cloud/backups/${BACKUP}
