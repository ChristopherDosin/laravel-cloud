{!! $domain !!} {
    {!! $tls !!}

    redir 301 {
        if {scheme} is http
        /  https://{!! str_replace([':80', ':443'], '', $domain) !!}{uri}
    }

    root {!! $root !!}

@if (! $index)
    header / X-Robots-Tag "noindex"
@endif

    gzip
    fastcgi / 127.0.0.1:9000 php

    limits {
        body 50mb
    }

    rewrite {
        to {path} {path}/ /index.php?{query}
    }
}
