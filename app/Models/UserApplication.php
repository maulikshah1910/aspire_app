<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserApplication extends Model
{
    use SoftDeletes;

    const STATUS_COMPLETED_YES = 1;
    const STATUS_COMPLETED_NO = 0;

    const STATUS_LOAN_REQUESTED = 0;
    const STATUS_LOAN_APPROVED = 1;
    const STATUS_LOAN_REJECTED = 2;
    const STATUS_LOAN_COMPLETED = 3;

    protected $fillable = [
        'user_id', 'amount', 'term', 'loan_status', 'is_completed',
        'interest_rate', 'weekly_repay_amount', 'amount_left'
    ];

    protected $hidden = [
        'deleted_at', 'loan_status', 'is_completed',
    ];

    public function getInterestRate()
    {
        if (isset($this->interest_rate)) {
            return $this->interest_rate;
        } else {
            return config('application.interest_rate');
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function installments()
    {
        return $this->hasMany(LoanPayment::class, 'loan_id','id');
    }
}
