import pika
import json

credentials = pika.PlainCredentials(
	"it490test",
	"admin1234!"
)

connection = pika.BlockingConnection(
	pika.ConnectionParameters(
		host="100.110.65.121",
		port = 5672,
		virtual_host="/",
		credentials = credentials
	)
)
channel = connection.channel()

message = {
	"type": "REGISTER",
	"name": "Test User",
	"email": "test@example.com",
	"password": "12345"
}

channel.basic_publish(
	exchange="",
	routing_key="request_queue",
	body=json.dumps(message)
)

print("LOGIN message sent!")

connection.close()
