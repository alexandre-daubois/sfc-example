<?php

namespace App\Controller;

// TODO(step-07): someone "tuned" frankenphp/conf.d/10-app.ini. Expose the state
// of OPcache on the private port only (6060): whether the cache is full, how many
// scripts it holds against its maximum, the hit rate, the misses and the restarts.
// opcache_get_status() has all of it.
class OpcacheController
{
}
