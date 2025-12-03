FROM python:3.11-slim

WORKDIR /app

ENV PYTHONUNBUFFERED=1

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY smart_guard_listener_create_schedule_session.py smart_guard_listener_student_attendance.py .

CMD ["python", "smart_guard_listener_create_schedule_session.py"]
