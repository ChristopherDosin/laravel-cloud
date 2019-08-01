
mkdir -p /home/cloud/.aws

# Write The Credentials File

cat > /home/cloud/.aws/credentials << EOF
[default]
aws_access_key_id = {!! $provider->meta['key'] !!}
aws_secret_access_key = {!! $provider->meta['secret'] !!}
EOF

# Write The Configuration File

cat > /home/cloud/.aws/config << EOF
[default]
output = json
region = {!! $provider->meta['region'] !!}
EOF
