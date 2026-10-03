<?php

namespace App\Models;

use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'topic', 'message', 'emailed_at', 'read_at'];

    protected function casts(): array
    {
        return ['emailed_at' => 'datetime', 'read_at' => 'datetime'];
    }

    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function topicLabel(): string
    {
        return config("portfolio.contact_topics.{$this->topic}", $this->topic);
    }
}
