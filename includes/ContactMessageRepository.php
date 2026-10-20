<?php

// Persistence for contact-form messages.
final class ContactMessageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(?int $userId, string $name, string $email, string $subject, string $message): void
    {
        $this->pdo->prepare(
            'INSERT INTO contact_messages (userID, name, email, subject, message) VALUES (?, ?, ?, ?, ?)'
        )->execute([$userId, $name, $email, $subject, $message]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->pdo->prepare('UPDATE contact_messages SET status = ? WHERE contactID = ?')->execute([$status, $id]);
    }

    public function listInbox(): array
    {
        return $this->pdo->query(
            "SELECT contactID, name, email, subject, message, status, created_at
             FROM contact_messages
             ORDER BY status = 'new' DESC, created_at DESC, contactID DESC"
        )->fetchAll();
    }
}
