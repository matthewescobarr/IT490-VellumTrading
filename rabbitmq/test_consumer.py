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

def callback(ch, method, properties, body):
	message = json.loads(body)
	print("Received message:")
	print(message)
	ch.basic_ack(
		deliver_tag=method.delivery_tag
	)
channel.basic_consume(
	queue="request_queue",
	on_message_callback=callback
)
print ("Waiting for messages...")

channel.start_consuming()
