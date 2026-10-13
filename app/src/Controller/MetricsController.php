<?php

namespace App\Controller;

// TODO(step-08): Ember only sees HTTP, and every POST /orders answers 202 in a
// few milliseconds. Serve Prometheus metrics on /metrics, on the private port
// only (6060), with promphp/prometheus_client_php and its APCu storage, which
// every PHP thread of FrankenPHP shares. Add a counter of the orders placed and
// a gauge of the messages waiting in the "async" transport.
class MetricsController
{
}
