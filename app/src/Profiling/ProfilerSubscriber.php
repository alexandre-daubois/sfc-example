<?php

namespace App\Profiling;

// TODO(step-03): SPX is loaded but never starts on its own (see
// frankenphp/conf.d/20-spx.ini). Turn this class into an event subscriber that
// calls spx_profiler_start() at the start of a main request carrying the
// "X-Spx-Profile" header, and spx_profiler_stop() once its response is ready.
// In worker mode the PHP request never ends, so SPX can't do it for you.
final class ProfilerSubscriber
{
}
