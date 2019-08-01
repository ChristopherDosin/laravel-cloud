
export DEBIAN_FRONTEND=noninteractive

# Wait For Apt To Unlock

while fuser /var/lib/dpkg/lock >/dev/null 2>&1 ; do
    echo "Waiting for other software managers to finish..."

    sleep 1
done

# Update & Install Packages

apt-get update
apt-get upgrade -y

apt-get install -y --force-yes build-essential \
                               curl \
                               fail2ban \
                               ufw \
                               software-properties-common \
                               supervisor \
                               whois

# Disable Password Authentication Over SSH

sed -i "/PasswordAuthentication yes/d" /etc/ssh/sshd_config
echo "" | sudo tee -a /etc/ssh/sshd_config
echo "" | sudo tee -a /etc/ssh/sshd_config
echo "PasswordAuthentication no" | sudo tee -a /etc/ssh/sshd_config

# Restart SSH

ssh-keygen -A
service ssh restart

# Set The Hostname

echo "{!! $script->provisionable->name !!}" > /etc/hostname
sed -i 's/127\.0\.0\.1.*localhost/127.0.0.1 {!! $script->provisionable->name !!} localhost/' /etc/hosts
hostname {!! $script->provisionable->name !!}

# Set The Timezone

ln -sf /usr/share/zoneinfo/UTC /etc/localtime

# Create The Root SSH Directory If Necessary

if [ ! -d /root/.ssh ]
then
    mkdir -p /root/.ssh
    touch /root/.ssh/authorized_keys
fi

# Setup Cloud User

useradd cloud
mkdir -p /home/cloud/.ssh
mkdir -p /home/cloud/.cloud
adduser cloud sudo

# Setup Bash For The Cloud User

chsh -s /bin/bash cloud
cp /root/.profile /home/cloud/.profile
cp /root/.bashrc /home/cloud/.bashrc

# Set The Sudo Password For The Cloud User

PASSWORD=$(mkpasswd {!! $script->provisionable->sudo_password !!})
usermod --password $PASSWORD cloud

# Build SSH Key Directories

mkdir -p /root/.ssh/authorized_keys.d
mkdir -p /home/cloud/.ssh/authorized_keys.d

# Write Local Key If Necessary

@if (app()->environment('local'))
cat > /root/.ssh/authorized_keys.d/local << EOF
# Local
{!! file_get_contents(env('TEST_SSH_CONTAINER_PUBLIC_KEY')) !!}
EOF

cp /root/.ssh/authorized_keys.d/local /home/cloud/.ssh/authorized_keys.d/local
@endif

# Write Owner Key

cat > /root/.ssh/authorized_keys.d/owner << EOF
# Owner
{{ $script->provisionable->project->user->public_worker_key }}
EOF

cp /root/.ssh/authorized_keys.d/owner /home/cloud/.ssh/authorized_keys.d/owner

# Generate Authorized Keys File

cat /root/.ssh/authorized_keys.d/* > /root/.ssh/authorized_keys
cat /home/cloud/.ssh/authorized_keys.d/* > /home/cloud/.ssh/authorized_keys

# Build Key Generation Cron

cat > /etc/cron.d/authorized_keys << EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

* * * * * cloud cat /home/cloud/.ssh/authorized_keys.d/* > /home/cloud/.ssh/authorized_keys
EOF

# Create The Server SSH Key

ssh-keygen -f /home/cloud/.ssh/id_rsa -t rsa -N ''

# Configure Supervisor

systemctl enable supervisor.service
service supervisor start

chmod 777 /etc/supervisor/conf.d

echo "cloud ALL=NOPASSWD: /usr/bin/supervisorctl *" > /etc/sudoers.d/supervisorctl

# Setup UFW Firewall

ufw allow 22
ufw allow 80
ufw allow 443
ufw --force enable

# Configure Logrotate

cat > /etc/logrotate.d/cloud-app << EOF
/home/cloud/app/storage/logs/*.log {
    su cloud cloud
    missingok
    size 10M
    rotate 5
    compress
    notifempty
    create 755 cloud cloud
}
EOF

cat > /etc/logrotate.d/cloud-scheduler << EOF
/home/cloud/scheduler.log {
    su cloud cloud
    missingok
    size 10M
    rotate 5
    compress
    notifempty
    create 755 cloud cloud
}
EOF

# Configure Swap Disk

if [ -f /swapfile ]; then
    echo "Swap exists."
else
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo "/swapfile none swap sw 0 0" >> /etc/fstab
    echo "vm.swappiness=30" >> /etc/sysctl.conf
    echo "vm.vfs_cache_pressure=50" >> /etc/sysctl.conf
fi

# Setup Unattended Security Upgrades

cat > /etc/apt/apt.conf.d/50unattended-upgrades << EOF
Unattended-Upgrade::Allowed-Origins {
    "Ubuntu zesty-security";
};
Unattended-Upgrade::Package-Blacklist {
    //
};
EOF

cat > /etc/apt/apt.conf.d/10periodic << EOF
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::AutocleanInterval "7";
APT::Periodic::Unattended-Upgrade "1";
EOF

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')
