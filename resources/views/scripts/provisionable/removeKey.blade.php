
# Remove Key & Regenerate Keys File

rm -f /home/cloud/.ssh/authorized_keys.d/{{ $name }}

cat /home/cloud/.ssh/authorized_keys.d/* > /home/cloud/.ssh/authorized_keys
