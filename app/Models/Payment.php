<?php

namespace App\Models;

use Core\Model;

class Payment extends Model
{
    protected string $table = 'payments';

    public function findByMollieId(string $mollieId): array|false
    {
        return $this->findBy('mollie_id', $mollieId);
    }

    public function findByOrder(int $orderId): array|false
    {
        return $this->findBy('order_id', $orderId);
    }
}
