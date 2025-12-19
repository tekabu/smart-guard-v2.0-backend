"""
MQTT listener that reacts to fingerprint verification responses and triggers the
Smart Guard REST APIs described in the requirements.

Dependencies:
    pip install paho-mqtt requests
"""

from __future__ import annotations

import json
import logging
import os
import signal
import sys
from typing import Any, Dict, List, Optional

import paho.mqtt.client as mqtt
import requests


logging.basicConfig(
    level=os.getenv("LOG_LEVEL", "INFO"),
    format="%(asctime)s [%(levelname)s] %(message)s",
)
LOGGER = logging.getLogger("smart_guard_fingerprint_listener")


MQTT_BROKER = os.getenv("MQTT_BROKER", "broker.emqx.io")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_KEEPALIVE = int(os.getenv("MQTT_KEEPALIVE", "60"))
MQTT_TOPICS = [
    topic.strip()
    for topic in os.getenv(
        "MQTT_TOPICS",
        ",".join(
            [
                "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-fingerprint-response"
            ]
        ),
    ).split(",")
    if topic.strip()
]
MQTT_USERNAME = os.getenv("MQTT_USERNAME")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD")

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


def fetch_user_by_fingerprint(fingerprint_id: str) -> Optional[Dict[str, Any]]:
    url = f"{API_BASE}/user-fingerprints/fingerprint/{fingerprint_id}"
    try:
        resp = requests.get(url, headers=_api_headers(), timeout=10)
        resp.raise_for_status()
    except requests.RequestException as exc:
        LOGGER.error("Failed to fetch fingerprint data for %s: %s", fingerprint_id, exc)
        return None

    payload = resp.json()
    if not payload.get("status"):
        LOGGER.warning(
            "Fingerprint lookup for %s returned status=false: %s",
            fingerprint_id,
            payload,
        )
        return None

    return payload.get("data")


def fetch_faculty_schedule(faculty_id: Any) -> List[Dict[str, Any]]:
    url = f"{API_BASE}/section-subject-schedules/faculty/{faculty_id}/current"
    try:
        resp = requests.get(url, headers=_api_headers(), timeout=10)
        resp.raise_for_status()
    except requests.RequestException as exc:
        LOGGER.error("Failed to fetch schedules for faculty %s: %s", faculty_id, exc)
        return []

    payload = resp.json()
    if not payload.get("status"):
        LOGGER.warning(
            "Schedule lookup for faculty %s returned status=false: %s",
            faculty_id,
            payload,
        )
        return []
    data = payload.get("data") or []
    if not isinstance(data, list):
        LOGGER.warning("Unexpected schedule payload for faculty %s: %s", faculty_id, data)
        return []
    return data


def create_schedule_session(section_subject_schedule_id: Any, client: mqtt.Client, position: Optional[str] = None) -> bool:
    url = f"{API_BASE}/schedule-sessions/create"
    params = {"start": "1"}
    data = {"section_subject_schedule_id": str(section_subject_schedule_id)}
    try:
        resp = requests.post(
            url,
            params=params,
            data=data,
            headers=_api_headers(),
            timeout=10,
        )
        resp.raise_for_status()
    except requests.RequestException as exc:
        LOGGER.error(
            "Failed to create session for schedule %s: %s",
            section_subject_schedule_id,
            exc,
        )
        return False

    payload = resp.json()
    if not payload.get("status"):
        LOGGER.warning(
            "Session creation for %s returned status=false: %s",
            section_subject_schedule_id,
            payload,
        )
        return False

    send_lock_command(client, position)

    LOGGER.info("Created session for schedule %s", section_subject_schedule_id)
    return True


def send_lock_command(client: mqtt.Client, position: Optional[str] = None) -> None:
    """Send lock command to the MQTT lock topic."""
    topic = "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-lock"
    payload = {"mode": "OPEN", "delay": 3}

    if position:
        payload["position"] = position

    try:
        result = client.publish(topic, json.dumps(payload))
        if result.rc == mqtt.MQTT_ERR_SUCCESS:
            LOGGER.info("Successfully sent lock command to topic %s", topic)
        else:
            LOGGER.error("Failed to send lock command to topic %s: %s", topic, result.rc)
    except Exception as exc:  # pylint: disable=broad-except
        LOGGER.error("Error sending lock command: %s", exc)


def send_no_schedule_notification(client: mqtt.Client) -> None:
    """
    Send notification to MQTT topic when no schedule is found.
    Topic: "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-fingerprint-response"
    {
        "log": "No schedule found"
    }
    """
    topic = "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-fingerprint-response"
    payload = {
        "log": "No schedule found"
    }

    try:
        result = client.publish(topic, json.dumps(payload))

        if result.rc == mqtt.MQTT_ERR_SUCCESS:
            LOGGER.info("Successfully sent no schedule notification to topic %s", topic)
        else:
            LOGGER.error("Failed to send no schedule notification to topic %s: %s", topic, result.rc)

    except Exception as exc:
        LOGGER.error("Error sending no schedule notification: %s", exc)


def process_fingerprint_payload(message: Dict[str, Any], client: mqtt.Client) -> None:
    if "data" not in message or "mode" not in message:
        LOGGER.warning("Payload missing required keys: %s", message)
        return

    if str(message.get("mode")).upper() != "VERIFY":
        LOGGER.info("Ignoring payload with unsupported mode: %s", message)
        return

    fingerprint_id = str(message.get("data")).strip()
    if not fingerprint_id:
        LOGGER.warning("Payload has empty fingerprint_id: %s", message)
        return

    position = message.get("position")

    LOGGER.info("Processing fingerprint_id %s", fingerprint_id)
    user_data = fetch_user_by_fingerprint(fingerprint_id)
    if not user_data:
        return

    user_info = user_data.get("user") or {}
    role = user_info.get("role")
    LOGGER.info("User %s role %s", user_info.get("name") or user_info.get("id"), role)

    if role != "FACULTY":
        LOGGER.info("User is not faculty; stopping after fingerprint verification.")
        return

    faculty_id = (
        user_info.get("id")
        or user_data.get("user_id")
        or user_info.get("faculty_id")
    )
    if faculty_id is None:
        LOGGER.warning("Faculty user missing id information: %s", user_data)
        return

    schedules = fetch_faculty_schedule(faculty_id)
    if not schedules:
        LOGGER.info("No schedules returned for faculty %s", faculty_id)
        send_no_schedule_notification(client)
        return

    for schedule in schedules:
        schedule_id = schedule.get("id")
        if schedule_id is None:
            LOGGER.warning("Schedule item missing id: %s", schedule)
            continue
        create_schedule_session(schedule_id, client, position)


def process_payload(topic: str, message: Dict[str, Any], client: mqtt.Client) -> None:
    if topic.endswith("smart-guard-fingerprint-response"):
        process_fingerprint_payload(message, client)
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
