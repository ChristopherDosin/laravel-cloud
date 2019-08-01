
# Write Caddyfile For Server

cat > /home/cloud/Caddyfile << EOF
{!! $script->actualDomainConfiguration() !!}
{!! $script->vanityDomainConfiguration() !!}
EOF

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')

# Restart Caddy

supervisorctl signal USR1 caddy
