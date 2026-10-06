<?php

final class Database
{
    public static function connect(array $config): PDO
    {
        foreach (['host', 'port', 'name', 'username'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
                throw new InvalidArgumentException('Missing database configuration: ' . $key);
            }
        }
        if (!isset($config['password']) || !is_string($config['password'])) {
            throw new InvalidArgumentException('Missing database configuration: password');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name']
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}