{!! $domain !!} {
    {!! $tls !!}

    redir 301 {
        if {scheme} is http
        /  https://{host}{uri}
    }

    proxy / {!! implode(' ', $proxyTo) !!} {
        transparent
        insecure_skip_verify
    }
}
