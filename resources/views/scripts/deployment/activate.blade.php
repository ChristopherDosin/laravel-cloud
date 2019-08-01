
set -e

APP_PATH="/home/cloud/app"
DEPLOYMENTS_PATH="/home/cloud/deployments"
DEPLOYMENT_PATH="$DEPLOYMENTS_PATH/{!! $deployment->timestamp() !!}"

# Activate New Deployment

echo "Activating Deployment"

ln -s $DEPLOYMENT_PATH /home/cloud/energize
mv -Tf /home/cloud/energize $APP_PATH

# Reload PHP-FPM

echo "Reloading FPM"

@if ($script->shouldRestartFpm())
sudo service php{{ $deployment->phpVersion() }}-fpm reload
@endif

# Run User Defined Activation Commands

cd $DEPLOYMENT_PATH

echo "Running User Activation Commands"

@foreach ($deployment->activation_commands as $command)
{!! $command !!}

@endforeach

# Delete Old Deployments

echo "Purging Old Deployments"

cd $DEPLOYMENTS_PATH

rm -rf `ls -t | tail -n +{{ 2 + 1 }}`
