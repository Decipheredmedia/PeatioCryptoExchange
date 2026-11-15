FROM python:3.12-slim
ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . .
RUN useradd --create-home assistant
USER assistant
EXPOSE 8090
# One worker keeps "waiting for your OK" actions in one place; threads handle
# several requests at once while Ollama is thinking.
CMD ["gunicorn", "-w", "1", "--threads", "8", "--timeout", "300", "-b", "0.0.0.0:8090", "app:app"]
