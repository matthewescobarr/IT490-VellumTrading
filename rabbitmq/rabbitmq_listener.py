import pika
import json

RABBITMQ_HOST = "100.110.65.121"
RABBITMQ_PORT = 5672
RABBITMQ_USER = "it490test"
RABBITMQ_PASSWORD = "admin1234!"

REQUEST_QUEUE = "request_queue"
RESPONSE_QUEUE = "reponse_queue"

def callback(ch, method, properties, body):
	try:
		message = json.loads(body)
		print ("\nReceived message:")
		print (message)
		message_type = message.get("type")

		if message_type == "LOGIN":
			print ("Login request received")
			print ("Email:", message.get("email"))

		elif message_type == "REGISTER":
			print ("Registration request received")
			print ("Name:", message.get("name"))
			print ("Email:", message.get("email"))

		else:
			print ("Unknown message type:", message_type)
		ch.basic_ack(delivery_tag=method.delivery_tag)
	except Exception as e:
		print ("Error processing message:", e)
		ch.basic_nack(
			delivery_tag = method.delivery_tag,
			requeue = False
		)
credentials = pika.PlainCredentials(
	RABBITMQ_USER,
	RABBITMQ_PASSWORD
)

connection = pika.BlockingConnection(
	pika.ConnectionParameters(
		host = RABBITMQ_HOST,
		port = RABBITMQ_PORT,
		virtual_host = "/",
		credentials = credentials
	)
)

channel = connection.channel()

channel.queue_declare(
	queue=REQUEST_QUEUE,
	durable = True
)

channel.queue_declare(
	queue=RESPONSE_QUEUE,
	durable=True 
)

channel.basic_qos(prefetch_count=1)

channel.basic_consume(
	queue = REQUEST_QUEUE,
	on_message_callback=callback
)

print("RabbitMQ listner started.")
print("Waiting for LOGIN or REGISTER messages...")
print("Press CTRL+C to stop")

try:
	channel.start_consuming()
except KeyboardINterrupt:
	print("\n Stopping listener...")
	connection.close()
