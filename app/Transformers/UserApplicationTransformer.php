<?php
namespace App\Transformers;

use App\Models\LoanPayment;
use App\Models\UserApplication;
use League\Fractal\TransformerAbstract;

/**
 * Transform User's loan application data in desired JSON to get loan info, loan user info and loan re-payment info
 *
 * Class UserApplicationTransformer
 * @package App\Transformers
 */
class UserApplicationTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     * @var array
     */
    protected $defaultIncludes = [
//        'user',
//        'repayments'
    ];

    /**
     * Transform user's loan application and get info of application along with its user info and repayment info
     * @param UserApplication $application
     * @return array
     */
    public function transform(UserApplication $application)
    {
        $applicationData = [
            'id' =>$application->id,
            'loan_amount' =>$application->amount,
            'loan_term' => $application->term,
            'application_status' => config('application.loan_status')[$application->loan_status],
            'loan_completed' =>config('application.loan_completed')[$application->is_completed],
            'amount_due' => $application->amount_left,
        ];

        $loanRepayments = LoanPayment::where('loan_id', $application->id)->orderBy('created_at', 'ASC')->get();
        $repayments = [];
        $totalAmountPaid = 0;
        $emi = 1;
        foreach($loanRepayments as $repayment) {
            $totalAmountPaid += $repayment->amount;
            $repayments[] = [
                'amount' => $repayment->amount,
                'amount_left' => $repayment->amount_left,
                'pay_date' => $repayment->created_at,
                'recursion' => $emi,
            ];
            $emi++;
        }
        $applicationData['amount_paid'] = number_format($totalAmountPaid, 2);
        $applicationData['repayments'] = $repayments;

        return $applicationData;
    }
}
