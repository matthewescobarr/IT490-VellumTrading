<?php

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/get_host_info.inc';

$config = getHostInfo(['DMZ/dmzRabbitMQ.ini'])['dmzServer'] ?? null;

if (!$config) {
    fwrite(STDERR, "Missing RabbitMQ configuration.\n");
    exit(1);
}
try {
    $connection = new AMQPConnection([
        'host' => $config['BROKER_HOST'],
        'port' => (int) $config['BROKER_PORT'],
        'login' => $config['USER'],
        'password' => $config['PASSWORD'],
        'vhost' => $config['VHOST'],
    ]);
    $connection->connect();
    $channel = new AMQPChannel($connection);

    $exchange = new AMQPExchange($channel);
    $exchange->setName($config['EXCHANGE']);
    $exchange->setType(AMQP_EX_TYPE_DIRECT);
    $exchange->setFlags(AMQP_DURABLE);
    $exchange->declareExchange();

    $queue = new AMQPQueue($channel);
    $queue->setName($config['QUEUE']);
    $queue->setFlags(AMQP_DURABLE);
    $queue->declareQueue();
    $queue->bind($config['EXCHANGE'], '*');

    echo 'Declared exchange ' . $config['EXCHANGE'] . ' and queue ' . $config['QUEUE'] . PHP_EOL;
    $connection->disconnect();
} catch (Throwable $e) {
    fwrite(STDERR, 'RabbitMQ setup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}