
export DEBIAN_FRONTEND=noninteractive

# Run Base Script

@include('scripts.provisionable.base')

# Run Caddy Installation Script

@include('scripts.caddy.install')

# Run PHP Installation Script

@include('scripts.php.install')

# Run Node Installation Script

@include('scripts.node.install')

# Run Database Installation Script

@include('scripts.database.install')

# Create Dummy App

mkdir -p /home/cloud/app/public
mkdir -p /home/cloud/maintenance/public

cat > /home/cloud/app/public/index.php << EOF
<?php echo "<?php phpinfo(); " ?>

EOF

cat > /home/cloud/maintenance/public/index.php << EOF
<?php echo "<?php http_response_code(503); " ?>
Site under maintenance.

EOF

# Write Caddyfile For Server

cat > /home/cloud/Caddyfile << EOF
{!! $script->actualDomainConfiguration() !!}
{!! $script->vanityDomainConfiguration() !!}
EOF

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')

# Update The Supervisor Configuration

supervisorctl reread
supervisorctl update

# Start Caddy

supervisorctl start caddy

# Run The Custom Scripts

@foreach ($customScripts as $customScript)
{!! $customScript !!}

@endforeach
