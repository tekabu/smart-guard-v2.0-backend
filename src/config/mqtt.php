<?php

return [
    'host' => env('MQTT_HOST', 'broker.emqx.io'),
    'port' => env('MQTT_PORT', 1883),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'client_id' => env('MQTT_CLIENT_ID'),
    'clean_session' => env('MQTT_CLEAN_SESSION', true),
    'keep_alive' => env('MQTT_KEEP_ALIVE', 60),
    'topics' => [
        'class_session' => env('MQTT_TOPIC_CLASS_SESSION', 'dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-lcd'),
        'rfid_response' => env('MQTT_TOPIC_RFID_RESPONSE', 'dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-rfid-response'),
    ],
    'qos' => env('MQTT_QOS', 0),
    'retain' => env('MQTT_RETAIN', false),
];
