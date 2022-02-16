<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use App\Models\UserApplication;
use App\Transformers\UserApplicationTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Fractal\Fractal;


class ApplicationController extends Controller
{
    /**
     * User applies for a loan
     *
     * @param Request $request
     * Request parameters
     * amount: 'required|min:1', (Loan Amount)
     * term: 'required|min:1', (Term is considered as weeks inputs)
     * interest: 'nullable', (Annual rate of interest over loan. This is optional field. If not passed, considered default interest rate from config file application.php)
     *
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     * data: [
     *  'loan_id' => ID (loan number) of a loan applied
     *  'loan_info' => information of loan applied along with amount, interest, term with status "Applied" and completion status "Not Completed"
     *  'amount_per_week' => An amount which can be re-payed by user in each term
     * ]
     *
     */
    public function apply(Request $request)
    {
        try {
            $user = Auth::user();

            $validation = Validator::make(
                $request->all(),
                [
                    'amount' => 'required|min:1',
                    'term' => 'required|min:1',
                ],
                [
                    'amount.required' => 'Please specify amount',
                    'term.required' => 'Please specify loan term',
                    'amount.min' => 'Minimum amount should be 1',
                    'term.required' => 'Loan should be given for at least 1 week',
                ]
            );

            if ($validation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validation->messages()
                ]);
            }

            $userApplication = new UserApplication();

            $loanInterest = $userApplication->getInterestRate();
            if ($request->has('interest')) {
                $loanInterest = $request->interest;
            }

            $amount = $request->get('amount');
            $term = $request->get('term');

            $getEMI = self::calculateEMI($amount, $loanInterest, $term);

            $userApplication->user_id = $user->id;
            $userApplication->amount = $amount;
            $userApplication->term = $term;
            $userApplication->interest_rate = $loanInterest;
            $userApplication->weekly_repay_amount = $getEMI;
            $userApplication->amount_left = $getEMI * $term;
            $userApplication->loan_status = UserApplication::STATUS_LOAN_REQUESTED;
            $userApplication->is_completed = UserApplication::STATUS_COMPLETED_NO;
            $userApplication->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'loan_id' => $userApplication->id,
                    'loan_info' => (new UserApplicationTransformer())->transform($userApplication),
                    'amount_per_week' => $getEMI,
                ]
            ], 200);
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }

    /**
     * If someone wants to know and check repayment amount based on amount and term given.
     *
     * @param Request $request
     * amount: 'required|min:1', (Loan Amount)
     * term: 'required|min:1', (Term is considered as weeks inputs)
     * interest: (Annual rate of interest over loan. This is optional field. If not passed, considered default interest rate from config file application.php)
     *
     * @param null $loanID: Installment can be calculated for recorded loan application or it can be calculated anonymously for getting information

     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     * data: [
     *  'term' => term of application in weeks,
     *  'amount' => loan amount,
     *  'rate' => interest rate for the loan,
     *  'repay_installment' => amount user can re-pay per week if takes loan
     * ]
     */
    public function calculateInstallment(Request $request, $loanID = null)
    {
        try {
            $amount = $request->get('amount');
            $term = $request->get('term');

            $application = UserApplication::find($loanID);
            $rate = ($application instanceof UserApplication) ? $application->getInterestRate() : (new UserApplication())->getInterestRate();
            if ($request->has('interest')) {
                $rate = $request->interest;
            }

            $amountPerWeek = self::calculateEMI($amount, $rate, $term);
            return response()->json([
                'success' => true,
                'data' => [
                    'term' => $term,
                    'amount' => $amount,
                    'rate' => $rate,
                    'repay_installment' => $amountPerWeek
                ]
            ], 200);
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }

    /**
     * Get loan application info along with its repayyments and their loan status and payment status
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     * 'data' => information of loan including loan status, amount left for re-pay and all re-payments done for this loan
     */
    public function applicationInfo($id)
    {
        try {
            $userApplication = UserApplication::with('user', 'installments')->find($id);
            if ($userApplication instanceof UserApplication) {
                $userApplication->loan_completed = config('application.loan_completed')[$userApplication->is_completed];
                $userApplication->application_status = config('application.loan_status')[$userApplication->loan_status];

//                return response()->collection($userApplication, new UserApplicationTransformer());
                return response()->json([
                    'success' => true,
                    'data' => (new UserApplicationTransformer())->transform($userApplication)
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Application ID'
                ], 404);
            }
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }

    /**
     * Specify approval of loan application, when application is in state of "Received"
     *
     * @param $id (Loan Application ID)
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     *  'data' => information of loan including loan status, amount left for re-pay and all re-payments done for this loan
     */
    public function approveApplication($id)
    {
        try {
            $userApplication = UserApplication::find($id);
            if (!$userApplication instanceof UserApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found..'
                ], 404);
            }
            if ($userApplication->loan_status == UserApplication::STATUS_LOAN_REQUESTED) {
                $userApplication->loan_status = UserApplication::STATUS_LOAN_APPROVED;
                $userApplication->save();

                return response()->json([
                    'success' => true,
                    'data' => (new UserApplicationTransformer())->transform($userApplication),
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot approve a loan application which is not in REQUESTED state'
                ], 200);
            }
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }

    /**
     * Specify rejection of loan application. When application is in state of "Received"
     *
     * @param $id (Loan Application ID)
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     *  'data' => information of loan including loan status, amount left for re-pay and all re-payments done for this loan
     */
    public function rejectApplication($id)
    {
        try {
            $userApplication = UserApplication::find($id);
            if (!$userApplication instanceof UserApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found..'
                ], 404);
            }
            if ($userApplication->loan_status == UserApplication::STATUS_LOAN_REQUESTED) {
                $userApplication->loan_status = UserApplication::STATUS_LOAN_REJECTED;
                $userApplication->save();

                return response()->json([
                    'success' => true,
//                    'data' => $userApplication,
                    'data' => (new UserApplicationTransformer())->transform($userApplication),
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot reject a loan application which is not in REQUESTED state'
                ], 200);
            }
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }

    /**
     * Receive payment for a loan application. If an amount parameter is passed, then consider that amount or else take default weekly repayment amount
     *
     *
     * @param Request $request
     * payment: 'nullable', if user specifies payment for re-payment, that amount is considered for re-payment. Or else, consider default amount from system per term
     *
     * @param $id (Loan Application ID)
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     *  'data' => information of loan including loan status, amount left for re-pay and all re-payments done for this loan
     *
     * When all the payments are settled and no amount left for next re-payment, the loan is set to closed status.
     *
     * This can return false in various conditions:
     * 1) When any exception is found
     * 2) When specified loan application is not found or it is not active loan
     * 3) When amount left for re-payment is less than amount input or default amount of re-payment is more
     */
    public function receivePayment(Request $request, $id)
    {
        try {
            $application = UserApplication::with('installments')->find($id);
            if (!$application instanceof UserApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found..',
                ], 404);
            }

            if ($application->loan_status == UserApplication::STATUS_LOAN_APPROVED && $application->is_completed == UserApplication::STATUS_COMPLETED_NO) {
                $repayAmount = $request->has('payment') ? $request->payment : $application->weekly_repay_amount;

                if ($application->amount_left < $repayAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => sprintf('You are left with amount %.02f and trying to pay amount %.02f which is more than loan repay amount left.Please input payment manually', $application->amount_left, $repayAmount),
                    ]);
                }
                LoanPayment::create([
                    'loan_id' => $id,
                    'amount' => $repayAmount,
                    'amount_left' => $application->amount_left - $repayAmount,
                ]);
                // update user application and reduce amount left

                $application->amount_left = $application->amount_left - $repayAmount;
                if ($application->amount_left <= 0) {
                    $application->is_completed = UserApplication::STATUS_COMPLETED_YES;
                    $application->loan_status = UserApplication::STATUS_LOAN_COMPLETED;
                }
                $application->save();

                $application = UserApplication::with('installments')->find($id);

                return response()->json([
                    'success' => true,
                    'data' => (new UserApplicationTransformer())->transform($application),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot accept payment for application which is not approved or closed',
                ], 404);
            }
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }

    /**
     * Get list of all loans along with associated repayments and amount left
     *
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     * 'data' => List of all loans of user along with their re-payment history and amount left for re-payment
     */
    public function userLoans()
    {
        try {
            $user = Auth::user()->id;
            $userApplications = UserApplication::where('user_id', $user)->get();

            $loanCollection = Fractal::create($userApplications, UserApplicationTransformer::class);
            return response()->json([
                'success' => true,
                'data' => $loanCollection->toArray(),
            ], 200);
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token Please Login Again'
                ], 200);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 200);
            }
        }
    }


    /**
     * Private method to calculat installment/repayment amount based on amount, rate and term specified.
     *
     * @param $amount: principal amount of loan
     * @param $rate : presumed to be yearly rate of interest (derived from config generally)
     * @param $term : presumed to be number of weeks
     * @return float
     */
    private function calculateEMI($amount, $rate, $term)
    {
        $interest = $rate / 5200; // interest we will take per periodic installment

        $aAmount = $interest * -$amount * pow((1 + $interest), $term) / (1 - pow((1 + $interest), $term));
        return number_format($aAmount, 2);
    }
}
