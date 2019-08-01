
# Stop The Daemons

echo "Stopping Supervisor Group: daemon-{!! $generation->id !!}"

nohup bash -c "sudo supervisorctl stop daemon-{!! $generation->id !!}:* && \
               sudo supervisorctl remove daemon-{!! $generation->id !!}  && \
               rm /etc/supervisor/conf.d/daemon-{!! $generation->id !!}.conf" > /dev/null 2>&1 &
