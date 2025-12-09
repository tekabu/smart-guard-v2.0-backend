"""
MQTT listener that reacts to LCD response events and closes schedule sessions
through the Smart Guard REST API.
"""

from __future__ import annotations

import json
import logging
import os
import signal
import sys
from typing import Any, Dict

import paho.mqtt.client as mqtt
import requests


logging.basicConfig(
    level=os.getenv("LOG_LEVEL", "INFO"),
    format="%(asctime)s [%(levelname)s] %(message)s",
)
LOGGER = logging.getLogger("smart_guard_listener_close_session")


MQTT_BROKER = os.getenv("MQTT_BROKER", "broker.emqx.io")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_KEEPALIVE = int(os.getenv("MQTT_KEEPALIVE", "60"))
MQTT_TOPICS = [
    topic.strip()
    for topic in os.getenv(
        "MQTT_TOPICS",
        "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-lcd-response",
    ).split(",")
    if topic.strip()
]
MQTT_USERNAME = os.getenv("MQTT_USERNAME")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD")
MQTT_CLEAR_TOPIC = os.getenv(
    "MQTT_CLEAR_TOPIC", "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-lcd"
)

API_BASE = os.getenv("API_BASE_URL", "http://172.17.38.182:8021/api")
API_TOKEN = os.getenv(
    "API_TOKEN",
    "58|FQntWTcX1ZSdKAp8ItJAhNBl9OBmYq2HYE6quIP605d554ef",
)


def _api_headers() -> Dict[str, str]:
    return {
        "Authorization": f"Bearer {API_TOKEN}",
        "Accept": "application/json",
    }


def close_schedule_session(session_id: Any) -> bool:
    url = f"{API_BASE}/schedule-sessions/{session_id}/close"
    try:
        resp = requests.post(url, headers=_api_headers(), timeout=10)
        resp.raise_for_status()
    except requests.RequestException as exc:
        LOGGER.error("Failed to close session %s: %s", session_id, exc)
        return False

    payload = resp.json()
    if not payload.get("status"):
        LOGGER.warning(
            "Close session request for %s returned status=false: %s",
            session_id,
            payload,
        )
        return False

    LOGGER.info("Closed schedule session %s", session_id)
    return True


def send_clear_lcd_command(client: mqtt.Client) -> None:
    payload = {"mode": "CLEAR_SESSION"}
    try:
        result = client.publish(MQTT_CLEAR_TOPIC, json.dumps(payload))
        if result.rc == mqtt.MQTT_ERR_SUCCESS:
            LOGGER.info("Sent CLEAR_SESSION command to %s", MQTT_CLEAR_TOPIC)
        else:
            LOGGER.error(
                "Failed to publish CLEAR_SESSION command to %s: rc=%s",
                MQTT_CLEAR_TOPIC,
                result.rc,
            )
    except Exception as exc:  # pylint: disable=broad-except
        LOGGER.error("Unexpected error sending CLEAR_SESSION command: %s", exc)


def process_lcd_payload(message: Dict[str, Any], client: mqtt.Client) -> None:
    mode = str(message.get("mode", "")).upper()
    if mode != "END_SESSION":
        LOGGER.info("Ignoring payload with unsupported mode: %s", message)
        return

    session_id = message.get("session_id")
    if session_id in (None, ""):
        LOGGER.warning("END_SESSION payload missing session_id: %s", message)
        return

    if close_schedule_session(session_id):
        send_clear_lcd_command(client)


def process_payload(topic: str, message: Dict[str, Any], client: mqtt.Client) -> None:
    if topic.endswith("smart-guard-lcd-response"):
        process_lcd_payload(message, client)
    else:
        LOGGER.info("Received payload on unhandled topic %s: %s", topic, message)


def _on_connect(client: mqtt.Client, userdata, flags, rc) -> None:
    if rc != 0:
        LOGGER.error("Failed to connect to MQTT broker: rc=%s", rc)
        return
    LOGGER.info("Connected to MQTT broker, subscribing to %s", ", ".join(MQTT_TOPICS))
    for topic in MQTT_TOPICS:
        client.subscribe(topic)


def _on_message(client: mqtt.Client, userdata, msg: mqtt.MQTTMessage) -> None:
    try:
        payload = msg.payload.decode("utf-8")
        message = json.loads(payload)
    except (UnicodeDecodeError, json.JSONDecodeError) as exc:
        LOGGER.error("Failed to decode payload on topic %s: %s", msg.topic, exc)
        return

    LOGGER.debug("Message on %s: %s", msg.topic, message)
    process_payload(msg.topic, message, client)


def build_mqtt_client() -> mqtt.Client:
    client = mqtt.Client()
    if MQTT_USERNAME:
        client.username_pw_set(MQTT_USERNAME, MQTT_PASSWORD)
    client.on_connect = _on_connect
    client.on_message = _on_message
    return client


def main() -> int:
    if not API_TOKEN:
        LOGGER.error("API_TOKEN is required")
        return 1

    client = build_mqtt_client()
    try:
        client.connect(MQTT_BROKER, MQTT_PORT, MQTT_KEEPALIVE)
    except Exception as exc:  # pylint: disable=broad-except
        LOGGER.error("Unable to connect to MQTT broker %s:%s - %s", MQTT_BROKER, MQTT_PORT, exc)
        return 1

    def handle_signal(signum, frame):  # pylint: disable=unused-argument
        LOGGER.info("Received signal %s, shutting down", signum)
        client.disconnect()

    signal.signal(signal.SIGINT, handle_signal)
    signal.signal(signal.SIGTERM, handle_signal)

    LOGGER.info("Listening for messages...")
    client.loop_forever()
    return 0


if __name__ == "__main__":
    sys.exit(main())
