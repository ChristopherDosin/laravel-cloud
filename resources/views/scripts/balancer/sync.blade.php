
# Rewrite Caddyfile

@if (count($script->balancer->project->allStacks()) > 0)
cat > /home/cloud/Caddyfile << EOF
{!! $script->actualDomainConfiguration() !!}
{!! $script->vanityDomainConfiguration() !!}
EOF
@else
cat > /home/cloud/Caddyfile << EOF
:80 {
    root /home/cloud/status
    tls off
}
EOF
@endif

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')

# Restart Caddy

supervisorctl signal USR1 caddy
