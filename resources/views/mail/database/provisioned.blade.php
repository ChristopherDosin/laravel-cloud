@component('mail::message')
# Database Created

A new database server has been created for the "{{ $database->project->name }}" project.
The server's credentials are:

**Server Name:** {{ $database->name }}

**Database Username:** {{ $database->username }}

**Database Password:** {{ $database->password }}

**Server Sudo Password:** {{ $database->sudo_password }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
