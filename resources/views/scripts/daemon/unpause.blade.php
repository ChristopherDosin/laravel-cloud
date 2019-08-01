
# Unpause The Daemons

echo "Unpausing Supervisor Group: daemon-{!! $generation->id !!}"
sudo supervisorctl signal CONT daemon-{!! $generation->id !!}:*
