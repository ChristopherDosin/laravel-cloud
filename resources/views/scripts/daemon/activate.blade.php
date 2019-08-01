
# Stop The Previous Daemon Generations

@foreach ($previousGenerations as $previousGeneration)
    if [ -f /etc/supervisor/conf.d/daemon-{!! $previousGeneration->id !!}.conf ]
    then
        echo "Stopping Supervisor Group: daemon-{!! $previousGeneration->id !!}"

        nohup bash -c "sudo supervisorctl stop daemon-{!! $previousGeneration->id !!}:* && \
                       sudo supervisorctl remove daemon-{!! $previousGeneration->id !!}  && \
                       rm /etc/supervisor/conf.d/daemon-{!! $previousGeneration->id !!}.conf" > /dev/null 2>&1 &
    fi
@endforeach

# Activate The Daemon Configuration

sleep 3

sudo supervisorctl add daemon-{!! $generation->id !!}
