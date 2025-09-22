<?php

namespace Modules\Sale\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function saleDetails() {
        return $this->hasMany(SaleDetails::class, 'sale_id', 'id');
    }

    public function salePayments() {
        return $this->hasMany(SalePayment::class, 'sale_id', 'id');
    }

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $number = Sale::max('id') + 1;
            $model->reference = make_reference_id('SL', $number);
        });
    }

    public function scopeCompleted($query) {
        return $query->where('status', 'Completed');
    }

    public function getShippingAmountAttribute($value) {
        return $value;
    }

    public function getPaidAmountAttribute($value) {
        return $value;
    }

    public function getTotalAmountAttribute($value) {
        return $value;
    }

    public function getDueAmountAttribute($value) {
        return $value;
    }

    public function getTaxAmountAttribute($value) {
        return $value;
    }

    public function getDiscountAmountAttribute($value) {
        return $value;
    }
}
