
# Build All Of The Daemon Configurations

<?php $programs = []; ?>

@foreach ($deployment->daemons() as $name => $daemon)
echo "Writing Daemon Supervisor Configuration"

# Define Some Variables

<?php $programs[] = $program = $name.'-'.$generation->id; ?>

# Write The Supervisor Configuration

cat >> /tmp/daemon-programs-{!! $generation->id !!}.conf << EOF
[program:{!! $program !!}]
command={!! $daemon['command'] !!}
directory={!! $daemon['directory'] ?? '/home/cloud/app' !!}
numprocs={!! $daemon['processes'] ?? 1 !!}
process_name=%(program_name)s_%(process_num)02d
user=cloud
autostart=true
autorestart=true
startsecs=3
startretries=3
stopsignal=TERM
stopwaitsecs={!! $daemon['wait'] ?? 60 !!}
stopasgroup=true
stdout_logfile=/home/cloud/daemon-{!! $name !!}.stdout
stderr_logfile=/home/cloud/daemon-{!! $name !!}.stderr
stdout_logfile_maxbytes=10MB
stderr_logfile_maxbytes=10MB

EOF

@endforeach

# Prepend Group To The Supervisor Configuration

cat > /tmp/daemon-group-{!! $generation->id !!} << EOF
[group:daemon-{!! $generation->id !!}]
programs={!! implode(',', $programs) !!}

EOF

# Generate Final Supervisor Configuration

cat /tmp/daemon-group-{!! $generation->id !!} /tmp/daemon-programs-{!! $generation->id !!}.conf \
    > /etc/supervisor/conf.d/daemon-{!! $generation->id !!}.conf

sudo supervisorctl reread
