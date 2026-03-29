<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Receipt extends Model
{
    protected string $table = 'receipts';

    public function findByCode(string $publicCode): ?array
    {
        return $this->findWhere('public_code', $publicCode);
    }
}
