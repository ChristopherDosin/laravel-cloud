
# Rewrite Script Into Another File

cat > {!! $path !!} << '{!! $token !!}'
{!! $task->script !!}

{!! $token !!}

# Invoke Script File

@if ($task->timeout() > 0)
timeout {!! $task->timeout() !!}s bash {!! $path !!}
@else
bash {!! $path !!}
@endif

# Call Home With ID & Status Code

STATUS=$?

curl --insecure {!! url('/api/callback/'.hashid_encode($task->id)) !!}?exit_code=$STATUS > /dev/null 2>&1
