<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'id_document_type',
        'name',
        'id_document_number',
        'id_bill_type',
        'address',
        'city',
        'country',
        'zipcode',
        'aasm_state',
        'verified',
    ];

    protected $casts = [
        'verified' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
