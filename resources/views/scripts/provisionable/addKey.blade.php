
# Write Key & Regenerate Keys File

cat > /home/cloud/.ssh/authorized_keys.d/{{ $name }} << EOF
# {{ $name }}
{{ $key }}

EOF

cat /home/cloud/.ssh/authorized_keys.d/* > /home/cloud/.ssh/authorized_keys
