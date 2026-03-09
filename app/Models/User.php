<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): array|false
    {
        return $this->findBy('email', $email);
    }

    public function getActive(): array
    {
        return $this->query(
            "SELECT * FROM users WHERE is_active = 1 ORDER BY name ASC"
        )->fetchAll();
    }
}
