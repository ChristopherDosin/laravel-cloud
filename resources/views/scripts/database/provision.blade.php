
export DEBIAN_FRONTEND=noninteractive

# Run Base Script

@include('scripts.provisionable.base')

# Run Database Installation Script

@include('scripts.database.install')

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')
