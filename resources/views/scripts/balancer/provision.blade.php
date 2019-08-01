# Run Base Script

@include('scripts.provisionable.base')

# Install Caddy

@include('scripts.caddy.install')

# Create Caddy Directories

mkdir /home/cloud/status

# Create Base Caddy Configuration

cat > /home/cloud/status/index.html << EOF
OK
EOF

cat > /home/cloud/Caddyfile << EOF
:80 {
    root /home/cloud/status
    tls off
}
EOF

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')

# Update The Supervisor Configuration

supervisorctl reread
supervisorctl update

# Start Caddy

supervisorctl start caddy
