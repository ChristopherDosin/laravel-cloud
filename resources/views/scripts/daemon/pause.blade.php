
# Pause The Daemons

echo "Pausing Supervisor Group: daemon-{!! $generation->id !!}"
sudo supervisorctl signal USR2 daemon-{!! $generation->id !!}:*
