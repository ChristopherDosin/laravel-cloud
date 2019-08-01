
# Set The Proper Directory Permissions

chown -R cloud:cloud /home/cloud
chmod -R 755 /home/cloud

chmod 700 /home/cloud/.ssh
chmod 700 /home/cloud/.ssh/authorized_keys.d

chmod 644 /home/cloud/.ssh/authorized_keys.d/*
chmod 644 /home/cloud/.ssh/authorized_keys
chmod 644 /home/cloud/.ssh/id_rsa.pub
chmod 600 /home/cloud/.ssh/id_rsa
