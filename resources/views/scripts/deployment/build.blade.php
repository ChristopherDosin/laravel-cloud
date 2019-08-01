
set -e

# Define Some Variables

TARBALL_PATH="/home/cloud/tarballs"
STORAGE_PATH="/home/cloud/directories"
DEPLOYMENTS_PATH="/home/cloud/deployments"
TARBALL="$TARBALL_PATH/{!! $deployment->hash() !!}"
DEPLOYMENT_PATH="/home/cloud/deployments/{!! $deployment->timestamp() !!}"
ENVIRONMENT_ENV="$DEPLOYMENT_PATH/.env.{!! $deployment->stack()->environment->name !!}"

# Remove The App Directory If It's Not A Symlink

if [ ! -h /home/cloud/app ]
then
    echo "Removing App Directory"
    rm -rf /home/cloud/app
fi

# Ensure Necessary Directories Exist

mkdir -p $TARBALL_PATH
mkdir -p $DEPLOYMENT_PATH

# Unpack Tarball To Deployment Directory

echo "Downloading Project Tarball"

wget {!! $deployment->tarballUrl() !!} -O "$TARBALL" --progress=dot:mega
tar -xvf $TARBALL -C "$DEPLOYMENT_PATH" --strip-components=1 > /dev/null
rm -f $TARBALL

# Create Environment File

touch $DEPLOYMENT_PATH/.env

if [ -f $ENVIRONMENT_ENV ]
then
    $ENVIRONMENT_ENV $DEPLOYMENT_PATH/.env
fi

@if ($deployment->environmentVariables())
echo "Writing Environment File"

cat > $DEPLOYMENT_PATH/.env << EOF
{!! $deployment->environmentVariables() !!}

EOF
@endif

# Add Stack Variables To Environment File

echo "Appending To Environment File"

cat >> $DEPLOYMENT_PATH/.env << EOF

APP_KEY={!! $deployable->stack->environment->encryption_key !!}

CLOUD_MASTER={!! $deployable->isMaster() ? 'true' : 'false' !!}
CLOUD_WORKER={!! $deployable->isWorker() ? 'true' : 'false' !!}
CLOUD_MASTER_WORKER={!! $deployable->isMasterWorker() ? 'true' : 'false' !!}

DB_CONNECTION=mysql
DB_HOST={!! $deployment->databaseHost() !!}
DB_DATABASE=cloud
DB_USERNAME=cloud
DB_PASSWORD={!! $deployment->databasePassword() !!}
DB_PORT=3306

@foreach ($deployment->stack()->databases as $database)
{!! $database->variableName() !!}_HOST={!! $database->address->private_address !!}
{!! $database->variableName() !!}_PASSWORD={!! $database->password !!}
@endforeach

REDIS_HOST={!! $deployment->databaseHost() !!}
BEANSTALKD_HOST={!! $deployment->databaseHost() !!}

EOF

# Ensure Storage Directory Exists

mkdir -p $STORAGE_PATH

# Link Directories

@foreach ($directories as $directory)
if [ -d $DEPLOYMENT_PATH/{{ $directory }} ]
then
    echo "Linking Directory ({{ $directory }})"

    STORAGE_DIRECTORY_PATH="${STORAGE_PATH}/{{ str_replace('/', '-', $directory) }}"

    mkdir -p ${STORAGE_DIRECTORY_PATH}
    cp -ar ${DEPLOYMENT_PATH}/{{ $directory }}/* ${STORAGE_DIRECTORY_PATH}
    rm -rf "${DEPLOYMENT_PATH}/{{ $directory }}"
    ln -s ${STORAGE_DIRECTORY_PATH} "${DEPLOYMENT_PATH}/{{ $directory }}"
fi
@endforeach

# Run User Defined Build Commands

echo "Running User Build Commands"

cd $DEPLOYMENT_PATH

@foreach ($deployment->build_commands as $command)
{!! $command !!}

@endforeach
