
# Rebuild UFW Rules

@foreach ($previousIpAddresses as $ipAddress)
ufw delete allow from {!! $ipAddress !!} to any port 3306
ufw delete allow from {!! $ipAddress !!} to any port 6379
ufw delete allow from {!! $ipAddress !!} to any port 11211
ufw delete allow from {!! $ipAddress !!} to any port 11300
@endforeach

@foreach ($ipAddresses as $ipAddress)
ufw allow from {!! $ipAddress !!} to any port 3306
ufw allow from {!! $ipAddress !!} to any port 6379
ufw allow from {!! $ipAddress !!} to any port 11211
ufw allow from {!! $ipAddress !!} to any port 11300
@endforeach
