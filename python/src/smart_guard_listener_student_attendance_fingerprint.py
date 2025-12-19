"""
MQTT listener for verifying student fingerprint scans and fetching attendance info.

Dependencies:
    pip install paho-mqtt requests
"""

from __future__ import annotations

import json
import logging
import os
import signal
import sys
from typing import Any, Dict, Optional

import paho.mqtt.client as mqtt
import requests


logging.basicConfig(
    level=os.getenv("LOG_LEVEL", "INFO"),
    format="%(asctime)s [%(levelname)s] %(message)s",
)
LOGGER = logging.getLogger("smart_guard_listener_student_attendance_fingerprint")


MQTT_BROKER = os.getenv("MQTT_BROKER", "broker.emqx.io")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_KEEPALIVE = int(os.getenv("MQTT_KEEPALIVE", "60"))
MQTT_TOPIC = os.getenv(
    "MQTT_TOPIC_FINGERPRINT",
    "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-fingerprint-response",
)
MQTT_USERNAME = os.getenv("MQTT_USERNAME")
MQTT_PASSWORD = os.getenv("MQTT_PASSWORD")

API_BASE = os.getenv("API_BASE_URL", "http://172.17.38.182:8021/api")
API_TOKEN = os.getenv(
    "API_TOKEN",
    "58|FQntWTcX1ZSdKAp8ItJAhNBl9OBmYq2HYE6quIP605d554ef",
)

LOCK_OPEN_DELAY = int(os.getenv("LOCK_OPEN_DELAY", "3"))


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
        LOGGER.error(
            "Failed to fetch fingerprint data for %s: %s",
            fingerprint_id,
            exc,
        )
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


def fetch_student_attendance(student_id: Any) -> Optional[Dict[str, Any]]:
    url = f"{API_BASE}/section-subject-schedules/student/{student_id}/attendance"
    try:
        resp = requests.post(url, headers=_api_headers(), timeout=10)
    except requests.RequestException as exc:
        LOGGER.error("Attendance request failed for student %s: %s", student_id, exc)
        return None

    try:
        payload = resp.json()
    except ValueError:
        LOGGER.error(
            "Attendance response for student %s is not valid JSON (status %s): %s",
            student_id,
            resp.status_code,
            resp.text,
        )
        return None

    if resp.status_code >= 400 or not payload.get("status"):
        LOGGER.warning(
            "Attendance lookup for student %s returned status=%s code=%s payload=%s",
            student_id,
            payload.get("status"),
            resp.status_code,
            payload,
        )
        return None

    return payload.get("data")


def send_lock_command(client: mqtt.Client, position: Optional[str] = None) -> None:
    """Send lock command to the MQTT lock topic."""
    topic = "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-lock"
    payload = {"mode": "OPEN", "delay": LOCK_OPEN_DELAY}

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


def send_access_denied_notification(client: mqtt.Client, position: Optional[str] = None, reason: str = "Access denied") -> None:
    """
    Send access denied notification to MQTT topic.
    Topic: "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-access-denied"
    {
        "position": "FRONT",
        "reason": "No attendance created"
    }
    """
    topic = "dJfmRURS5LaJtZ1NZAHX86A9uAk4LZ-smart-guard-access-denied"
    payload = {
        "reason": reason
    }

    if position:
        payload["position"] = position

    try:
        result = client.publish(topic, json.dumps(payload))

        if result.rc == mqtt.MQTT_ERR_SUCCESS:
            LOGGER.info("Successfully sent access denied notification to topic %s", topic)
        else:
            LOGGER.error("Failed to send access denied notification to topic %s: %s", topic, result.rc)

    except Exception as exc:  # pylint: disable=broad-except
        LOGGER.error("Error sending access denied notification: %s", exc)


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
        send_access_denied_notification(client, position, "Fingerprint not registered")
        return

    user_info = user_data.get("user") or {}
    role = user_info.get("role")
    LOGGER.info("User %s role %s", user_info.get("name") or user_info.get("id"), role)

    if role != "STUDENT":
        LOGGER.info("User is not a student; no attendance lookup will be made.")
        return

    student_id = (
        user_info.get("id")
        or user_data.get("user_id")
        or user_info.get("student_id")
    )
    if student_id is None:
        LOGGER.warning("Student user missing id information: %s", user_data)
        return

    attendance = fetch_student_attendance(student_id)
    if attendance is None:
        send_access_denied_notification(client, position, "No attendance created")
        return

    LOGGER.info(
        "Fetched attendance payload for student %s: %s",
        student_id,
        attendance,
    )

    send_lock_command(client, position)


def _on_connect(client: mqtt.Client, userdata, flags, rc) -> None:
    if rc != 0:
        LOGGER.error("Failed to connect to MQTT broker: rc=%s", rc)
        return
    LOGGER.info("Connected to MQTT broker, subscribing to %s", MQTT_TOPIC)
    client.subscribe(MQTT_TOPIC)


def _on_message(client: mqtt.Client, userdata, msg: mqtt.MQTTMessage) -> None:
    try:
        payload = msg.payload.decode("utf-8")
        message = json.loads(payload)
    except (UnicodeDecodeError, json.JSONDecodeError) as exc:
        LOGGER.error("Failed to decode payload on topic %s: %s", msg.topic, exc)
        return

    LOGGER.debug("Message on %s: %s", msg.topic, message)
    process_fingerprint_payload(message, client)


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
        LOGGER.error(
            "Unable to connect to MQTT broker %s:%s - %s",
            MQTT_BROKER,
            MQTT_PORT,
            exc,
        )
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
