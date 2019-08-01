@component('mail::message')
# Stack Created

A new stack has been created for the "{{ $stack->environment->project->name }}" project.
The stack's credentials are:

**Stack Name:** {{ $stack->name }}

@foreach ($stack->allServers() as $server)
**{{ $server->name }} Sudo Password:** {{ $server->sudo_password }}

@endforeach

Thanks,<br>
{{ config('app.name') }}
@endcomponent
