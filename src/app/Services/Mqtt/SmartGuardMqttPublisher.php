<?php

namespace App\Services\Mqtt;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use Throwable;

class SmartGuardMqttPublisher
{
    public function publish(array $payload, ?string $overrideTopic = null): void
    {
        $host = Config::get('mqtt.host');
        $topic = $overrideTopic ?? Config::get('mqtt.topics.class_session');

        if (!$host || !$topic) {
            return;
        }

        $clientId = Config::get('mqtt.client_id') ?? sprintf('smart-guard-api-%s', uniqid());
        $client = new MqttClient($host, (int) Config::get('mqtt.port', 1883), $clientId);

        $connectionSettings = (new ConnectionSettings())
            ->setUsername(Config::get('mqtt.username'))
            ->setPassword(Config::get('mqtt.password'))
            ->setKeepAliveInterval((int) Config::get('mqtt.keep_alive', 60));

        try {
            $client->connect($connectionSettings, (bool) Config::get('mqtt.clean_session', true));
            $client->publish(
                $topic,
                json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                (int) Config::get('mqtt.qos', 0),
                (bool) Config::get('mqtt.retain', false)
            );
            $client->disconnect();
        } catch (MqttClientException|Throwable $exception) {
            Log::error('Failed to publish MQTT payload.', [
                'payload' => $payload,
                'exception' => $exception->getMessage(),
            ]);

            if (isset($client) && $client->isConnected()) {
                $client->disconnect();
            }
        }
    }
}
