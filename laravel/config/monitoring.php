<?php

/*
|--------------------------------------------------------------------------
| Monitoring
|--------------------------------------------------------------------------
|
| Failure alerts (failed background jobs, a failed nightly backup or billing
| check) are emailed to these addresses and, with tenancy, to every platform
| admin. The same alert is sent at most once per throttle period.
|
*/

return [

    'alert_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('ALERT_EMAILS', ''))))),

    'alert_throttle_minutes' => (int) env('ALERT_THROTTLE_MINUTES', 60),

];
