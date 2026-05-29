<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileItemShare extends Model
{
    protected $table = 'file_manager_shares';

    protected $fillable = [
        'file_item_id',
        'user_id',
        'permission',
        'requires_ack',
        'ack_code',
        'ack_code_expires_at',
        'ack_completed_at',
    ];

    protected $casts = [
        'requires_ack' => 'boolean',
        'ack_code_expires_at' => 'datetime',
        'ack_completed_at' => 'datetime',
    ];

    public function fileItem()
    {
        return $this->belongsTo(FileItem::class, 'file_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
