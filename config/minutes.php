<?php

$alertRecipients = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env(
        'POLICY_ALERT_RECIPIENTS',
        'asesor1@grupoinfodata.com,mcazares@grupoinfodata.com',
    )),
)));

return [
    'imported_services' => (bool) env('MINUTES_IMPORTED_SERVICES', true),
    'minute_cc' => env('MINUTE_CC_ADDRESS', 'atencion@infodata.mx'),
    'policy_alert_threshold' => (int) env('POLICY_ALERT_THRESHOLD', 10),
    'policy_alert_recipients' => $alertRecipients,
];
