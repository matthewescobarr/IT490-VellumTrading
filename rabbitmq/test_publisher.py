import pika
import json

RABBITMQ_HOST = "100.110.65.121"
RABBITMQ_USER = "jamessass"
RABBITMQ_PASSWORD = "Summerroxie1469!"

credentials = pika.PlainCredentials(
	RABBITMQ_USER,
	RABBITMQ_PASSWORD
)

connection = pika.BlockingConnection(
	pika.ConnectionParameters(
		host=RABBITMQ_HOST,
		port=5672,
		virtual_host="/",
		credentials=credentials
	)
)

channel = connection.channel()

message = {
	"type": "TEST",
	"message": "Hellow from the RabbitMQ test publisher"
}

channel.basic_publish(
	exchange="",
	routing_key="request_queue",
	body=json.dumps(message)
)

print("Mesage sent to request_queue!")

connection.close()
