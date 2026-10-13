<?php

namespace App\Logging;

// TODO(step-02): FrankenPHP gives every request an X-Request-ID header and writes
// it in the access log (see frankenphp/Caddyfile). Turn this class into a Monolog
// processor that adds the ID of the current request to every log record, so the
// error lines of a request can be matched with its access line.
final class RequestIdProcessor
{
}
