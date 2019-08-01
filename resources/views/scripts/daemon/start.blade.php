
# Start The Daemons

echo "Starting Supervisor Group: daemon-{!! $generation->id !!}"
sudo supervisorctl add daemon-{!! $generation->id !!}
