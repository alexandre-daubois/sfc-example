<?php

// Loadgen sends a steady stream of requests to the shop through FrankenPHP.
//
//   php loadgen.php                                  baseline traffic
//   php loadgen.php -c 1 --pause=1000 /sync          one worker, one request per second
//   php loadgen.php --rate=300                       fixed rate, whatever the response time
//   php loadgen.php -c 2 'POST /orders'              any method in front of the path
//
// "{id}" is replaced by a random product ID on every request, a few of which do
// not exist so the baseline carries some 404s. "{uuid}" is replaced by a fresh
// random identifier.

$options = getopt('c:', ['pause:', 'rate:', 'target:', 'host:'], $rest);
$workers = (int) ($options['c'] ?? 10);
$pauseMs = (int) ($options['pause'] ?? 20);
$rate = (int) ($options['rate'] ?? 0);
$target = $options['target'] ?? 'http://php:8800';
$host = $options['host'] ?? 'shop.localhost';

$paths = array_slice($argv, $rest) ?: ['/products', '/products/{id}', '/products/{id}', '/products/{id}'];

$multi = curl_multi_init();
$ok = $serverErrors = $failures = 0;

$start = static function () use ($multi, $paths, $target, $host): void {
    $spec = $paths[array_rand($paths)];
    [$method, $path] = str_contains($spec, ' ') ? explode(' ', $spec, 2) : ['GET', $spec];
    $path = str_replace('{id}', (string) random_int(1, 2100), $path);
    $path = str_replace('{uuid}', bin2hex(random_bytes(16)), $path);

    $handle = curl_init($target.$path);
    curl_setopt_array($handle, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Host: '.$host],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_multi_add_handle($multi, $handle);
};

// In closed-loop mode, each worker sends its next request once the previous one
// returned, plus the pause. In fixed-rate mode, requests start on a timer.
$nextStart = array_fill(0, $rate > 0 ? 0 : $workers, 0.0);
$nextTick = microtime(true);
$nextReport = microtime(true) + 5;

echo $rate > 0
    ? sprintf("%d req/s on %s (Host: %s), paths %s, Ctrl+C to stop\n", $rate, $target, $host, implode(' ', $paths))
    : sprintf("%d workers on %s (Host: %s), paths %s, Ctrl+C to stop\n", $workers, $target, $host, implode(' ', $paths));

while (true) {
    $now = microtime(true);

    if ($rate > 0) {
        while ($nextTick <= $now) {
            $start();
            $nextTick += 1 / $rate;
        }
    } else {
        foreach ($nextStart as $worker => $at) {
            if (null !== $at && $at <= $now) {
                $start();
                $nextStart[$worker] = null;
            }
        }
    }

    curl_multi_exec($multi, $active);
    if (-1 === curl_multi_select($multi, 0.005)) {
        usleep(1000);
    }

    while ($done = curl_multi_info_read($multi)) {
        $handle = $done['handle'];
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        match (true) {
            CURLE_OK !== $done['result'] => ++$failures,
            $status >= 500 => ++$serverErrors,
            default => ++$ok,
        };
        curl_multi_remove_handle($multi, $handle);

        if ($rate > 0) {
            continue;
        }

        $idle = array_search(null, $nextStart, true);
        $nextStart[$idle] = microtime(true) + $pauseMs / 1000;
    }

    if ($now >= $nextReport) {
        printf("%s  ok=%d  5xx=%d  failed=%d\n", date('H:i:s'), $ok, $serverErrors, $failures);
        $ok = $serverErrors = $failures = 0;
        $nextReport += 5;
    }
}
