<?php

namespace App\Helpers;

use App\Models\Notification;

class NotificationHelpers
{
    public function CreateNotification( string $type, string $content, int $id )
    {
        $data = [
            "type" => $type,
            "content" => $content,
            "transactionId" => $id,
        ];

        return Notification::create($data);
    }
}
