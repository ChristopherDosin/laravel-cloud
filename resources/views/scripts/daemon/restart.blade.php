
# Write Fresh Supervisor Configuration

{!! $script->daemonConfiguration() !!}

# Reload Daemons & Stop & Remove Old Ones

{!! $script->activateDaemons() !!}
