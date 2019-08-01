
# Install NodeJS

curl --silent --location https://deb.nodesource.com/setup_8.x | bash -
apt-get update
sudo apt-get install -y --force-yes nodejs

# Install Yarn

curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get update && sudo apt-get install yarn
