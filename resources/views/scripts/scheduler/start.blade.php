
# Start The Scheduled Tasks

rm -f /etc/cron.d/schedule-*

@foreach ($deployment->schedule() as $name => $options)
cat > /etc/cron.d/schedule-{{ $name }} << EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

{{ $options['frequency'] }} {{ $options['user'] ?? 'cloud' }} {{ $options['command'] }} >> /home/cloud/schedule-{{ $name }}.log 2>&1
EOF
@endforeach
