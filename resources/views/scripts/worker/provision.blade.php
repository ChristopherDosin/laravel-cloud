
export DEBIAN_FRONTEND=noninteractive

# Run Base Script

@include('scripts.provisionable.base')

# Run PHP Installation Script

@include('scripts.php.install')

# Make Sure Directories Have Correct Permissions

@include('scripts.tools.chown')

# Run The Custom Scripts

@foreach ($customScripts as $customScript)
{!! $customScript !!}

@endforeach
