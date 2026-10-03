<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'topic', 'message', 'emailed_at'];

    protected function casts(): array
    {
        return ['emailed_at' => 'datetime'];
    }

    public function topicLabel(): string
    {
        return config("portfolio.contact_topics.{$this->topic}", $this->topic);
    }
}
