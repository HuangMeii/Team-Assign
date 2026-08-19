<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $primaryKey = 'notification_id';
    
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'url',
        'is_read',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for user's notifications
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get icon based on notification type
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            'topic_request' => 'fa-clipboard-check',
            'join_request' => 'fa-user-plus',
            'invite' => 'fa-envelope',
            'topic_approved' => 'fa-check-circle',
            'topic_rejected' => 'fa-times-circle',
            'group_joined' => 'fa-users',
            default => 'fa-bell',
        };
    }

    /**
     * Get color based on notification type
     */
    public function getColorAttribute()
    {
        return match($this->type) {
            'topic_request' => 'primary',
            'join_request' => 'info',
            'invite' => 'warning',
            'topic_approved' => 'success',
            'topic_rejected' => 'danger',
            'group_joined' => 'success',
            default => 'secondary',
        };
    }
}