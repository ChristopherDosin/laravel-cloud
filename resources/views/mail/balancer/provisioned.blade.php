@component('mail::message')
# Balancer Created

A new balancer server has been created for the "{{ $balancer->project->name }}" project.
The server's credentials are:

**Server Name:** {{ $balancer->name }}

**Sudo Password:** {{ $balancer->sudo_password }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
