<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActionLogged
{
    use Dispatchable, SerializesModels;

    public $userId;
    public $action;
    public $metadata;

    public function __construct($userId, $action, array $metadata = [])
    {
        $this->userId = $userId;
        $this->action = $action;
        $this->metadata = $metadata;
    }
}

