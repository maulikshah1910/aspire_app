<?php
namespace App\Transformers;


use App\Models\LoanPayment;
use League\Fractal\TransformerAbstract;

class LoanRepaymentTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     * @var array
     */
    protected $defaultIncludes = [];

    public function transform(LoanPayment $repayment)
    {
        $data = [
            'id' => $repayment->id,
            'amount_paid' => $repayment->amount,
            'date' => $repayment->created_at,
            'loan_id' => $repayment->id,
        ];

        return $data;
    }
}
