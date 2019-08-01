{!! $domain !!} {
    {!! $tls !!}

    redir 301 {
        /  https://{!! $canonicalDomain !!}{uri}
    }
}
