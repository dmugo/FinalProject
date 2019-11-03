namespace App\Http\Controllers\Api;


use App\AppUser;
use App\CashFlow;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\UniversalMethods;
use App\Loan;
use App\LoanRepaymentRequest;
use App\MpesaRepaymnt;
use App\MpesaVerification;
use App\PaybillBalance;
use App\Repayment;
use App\StkPushPaymentRequest;
use App\VerificationMpesaCall;
use App\VerificationMpesaPayment;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\MpesaCallBack;

use App\MpesaCall;

use Illuminate\Http\Response;

use App\Payment;


class MpesaController extends Controller

{
//Register URLS for validation and Confirmation...used only once as a setup..use postman to fire it up
public function registerUrls()
{
//Variables specific to this application

$merchant_id = "600000"; //C2B Shortcode/Paybill

$confirmation_url = "https://linq.mobi/api/confirmation";//"https://linq.mobi/api/confirmation"; //Always avoid IP..coz of ssl issues
//        $confirmation_url = "https://23e87f38.ngrok.io/api/confirmation";//"https://linq.mobi/api/confirmation"; //Always avoid IP..coz of ssl issues

$validation_url = "https://linq.mobi/api/validation"; //"https://linq.mobi/api/validation";
//        $validation_url = "https://23e87f38.ngrok.io/api/validation"; //"https://linq.mobi/api/validation";

$access_token = $this->getAccessToken();

//START CURL

$url = 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl';
//        $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $url);

curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token)); //setting custom header


//          $curl_post_data = array(
//
//              'ShortCode' => 600000,
//
//              'ResponseType' => 'cancelled',
//
//              'ConfirmationURL' => 'https://23e87f38.ngrok.io/api/confirmation',
//
//              'ValidationURL' => 'https://23e87f38.ngrok.io/api/validation'
//
//          );

//          dd($curl_post_data);

$curl_post_data = array(

'ShortCode' => $merchant_id,

'ResponseType' => 'completed',

'ConfirmationURL' => $confirmation_url,

'ValidationURL' => $validation_url

);


$data_string = json_encode($curl_post_data);


curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_POST, true);

curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);


$curl_response = curl_exec($curl);

print_r($curl_response);

//        echo $curl_response;

}

//    simulating a C2B transaction

/**
* @param Request $request
* @return false|string
*/

public function c2bSimulator(Request $request)
{
$url = 'https://api.safaricom.co.ke/mpesa/c2b/v1/simulate';
//        $url = 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';
$token = $this->getAccessToken();

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $token)); //setting custom header

$curl_post_data = $request->toArray();
//        dd($curl_post_data);
//
//        $curl_post_data = array(
//            //Fill in the request parameters with valid values
//            'ShortCode' => '600000',
//            'CommandID' => 'CustomerPayBillOnline',
//            'Amount' => '100',
//            'Msisdn' => '254708374149',
//            'BillRefNumber' => '254708374149'
//        );
//        dd($curl_post_data);
$loan = Loan::join('app_users', 'loans.user_id', '=', 'app_users.id')
->select('loans.*')
->where(function ($q) {
$q->where('loans.loan_status', 1)
->orWhere('loans.loan_status', 3);
})
->where('app_users.phone_number', $curl_post_data['Msisdn'])
->first();
//        dd($loan);

$repay = new LoanRepaymentRequest();
$repay->loan_id = $loan->id;
$repay->amount = $curl_post_data['Amount'];
$repay->status = 1;
$repay->save();

//        LoanRepaymentRequest::create([
//            'loan_id' => $loan->id,
//            'amount' => $curl_post_data['Amount'],
//            'status' => 1
//        ]);

//        dd($repay);

$data_string = json_encode($curl_post_data);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

$curl_response = curl_exec($curl);

$result = json_decode($curl_response);
return json_encode($result);
//       echo $curl_response;
}


//C2B Functions ############################################### START

//Lipa na M-Pesa Online Payment - Resource URL


/**
* @param Request $request (user id and the amount to be paid)
* @return false|string
*/

public function stkPush(Request $request) //From AJAX call/android side, etc....start USSD pay request

{
//        dd($request);
$amount = $request->amount;

$user_id = $request->user_id;

$user = AppUser::where('id', $user_id)
->first();
//        dd($user);

$phone_paying = $user->phone_number;


//remove 07 for those that come with it

if ($this->startsWith($phone_paying, "07")) {

$pos = strpos($phone_paying, "07");

if ($pos !== false) {

$phone_paying = substr_replace($phone_paying, "2547", $pos, 2);

}


//$phone_paying=str_replace("0","254",$phone_paying);

}


//Validate the inputs

if ($amount === "" || $phone_paying === "" || $user_id === "" || !$this->startsWith($phone_paying, "2547") ||

$amount < 1 || $amount > 70000 || $user_id < 1 || filter_var($user_id, FILTER_VALIDATE_INT) === false ||

filter_var($amount, FILTER_VALIDATE_INT) === false) {

return json_encode(
[
"success" => 0,
"message" => "Wrong input"
]);

}


//Variables specific to this application

$merchant_id = "174379"; //C2B Shortcode/Paybill

$callback_url = "https://linq.mobi/api/callback"; //"https://linq.mobi/api/callback";

$passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919"; //Ask from Safaricom guys..


$account_reference =  $phone_paying; //like account number while paying via paybill

$transaction_description = 'Pay for User:' . $phone_paying;

$loan = Loan::where('user_id', $user->id)
->where(function ($q) {
$q->where('loan_status', 1)
->orWhere('loan_status', 3);
})
->first();

//        return $loan;
//LOG the Request. This is done just to keep a record of the online payment calls you have made

$call = new MpesaCall();

$caller = "C2B_ONLINE";

$ip = $request->ip();

$content = "Phone: " . $phone_paying . " | Amount: " . $amount;

$call->content = $content;

$call->ip = $ip;

$call->caller = $caller;

$call->save();


//Initiate PUSH

$timestamp = date("YmdHis");

$password = base64_encode($merchant_id . $passkey . $timestamp); //No more Hashing like before. this is a guideline from Saf

$access_token = $this->getAccessToken();


$curl = curl_init();

$endpoint_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

curl_setopt($curl, CURLOPT_URL, $endpoint_url);

curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer ' . $access_token)); //setting custom header


$curl_post_data = array(

'BusinessShortCode' => $merchant_id,

'Password' => $password,

'Timestamp' => $timestamp,

'TransactionType' => 'CustomerPayBillOnline',

'Amount' => $amount,

'PartyA' => $phone_paying,

'PartyB' => $merchant_id,

'PhoneNumber' => $phone_paying,

'CallBackURL' => $callback_url,

'AccountReference' => $account_reference,

'TransactionDesc' => $transaction_description

);


$data_string = json_encode($curl_post_data);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_POST, true);

curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

$curl_response = curl_exec($curl);
//        dd($curl_response);

$result = json_decode($curl_response);
//        dd($result);


//        return $curl_response;

StkPushPaymentRequest::create([
'loan_id' => $loan->id,
'merchant_request_id' => $result->MerchantRequestID,
'checkout_request_id' => $result->CheckoutRequestID,
'amount' => $amount,
'status' => 1
]);

if (array_key_exists("errorCode", $result)) { //Error

return json_encode(
[
"success" => 0,
"message" => "Request Failed"
]);

} else if ("ResponseCode" == 0) { //Success
//create the pending request...

return json_encode([
"success" => 1,
"message" => "Request Sent Successfully"
]);
//            return response()->json(['result'=> $result]);

} else {

return json_encode([
"success" => 0,
"message" => "Unknown Error, Please Retry"
]);

}

}


/*

* initial settings functions..callback/success/failure?

* this method receives feedback from Safaricom after request

* is successfully sent to user. But remember may cancel, low balance, pay etc

*/

public function callback(Request $request)
{ //ONLY in STK push requests
//        $body = $request->Body;

//        $myresponse= response()->json(['body'=> $body]);

$content = json_encode($request->getContent());

$result_code = $request->Body['stkCallback']['ResultCode'];
$mercahnt_id = $request->Body['stkCallback']['MerchantRequestID'];
$checkout_id = $request->Body['stkCallback']['CheckoutRequestID'];

$identifier = "C2B_CALLBACK";

$ip = $request->ip();

$callback = new MpesaCall();

$callback->ip = $ip;

$callback->content = $content;

$callback->caller = $identifier;

$callback->save();

$stk_repayment_request = StkPushPaymentRequest::where('merchant_request_id', $mercahnt_id)
->first();

//TODO check for the bug that returns a wrong loan for a repayment by stk push
$loan = Loan::join('stk_push_payment_requests', 'loans.id', '=', 'stk_push_payment_requests.loan_id')
->join('app_users', 'loans.user_id', '=', 'app_users.id')
->select('loans.*', 'app_users.first_name as fname', 'app_users.second_name as sname',
'app_users.surname as surname', 'app_users.phone_number as phone')
->where('stk_push_payment_requests.id', $stk_repayment_request->id)
->first();

if ($result_code == 0) {

$stk_repayment_request->status = 2;
$stk_repayment_request->save();
/*$item = $request->Body['stkCallback']['CallbackMetadata']['Item'][0]['Value'];

$balance = 0;
$amount = $request->Body['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
$trans_id = $request->Body['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
$time = $request->Body['stkCallback']['CallbackMetadata']['Item'][3]['Value'];


$mpesa_repayment = new MpesaRepaymnt();
$mpesa_repayment->identifier = 'STK_PUSH_REPAYMENT';
$mpesa_repayment->amount = $amount;
$mpesa_repayment->transaction_id = $trans_id;
$mpesa_repayment->balance = $balance;
$mpesa_repayment->msisdn = $loan->phone;
$mpesa_repayment->first_name = $loan->fname;
if($loan->sname != null){
$mpesa_repayment->middle_name = $loan->sname;
}
$mpesa_repayment->last_name = $loan->surname;
$mpesa_repayment->bill_reference = $loan->phone;
$mpesa_repayment->time = $time;
$mpesa_repayment->save();

$rep = Repayment::where('loan_id', $loan->id)
->orderby('id', 'desc')->first();
$total_loan = $loan->principal_amount + $loan->interest_amount;

if ($rep === null) {
$balance = $total_loan - $amount;
} else {
$prev_balance = $rep->balance;
$balance = $prev_balance - $amount;
}

if ($balance < 0) {


$phone_number = UniversalMethods::formatPhoneNumber($loan->phone);
$fname = $loan->fname;

$message = "Hi, "
. $fname. ". The amount you are paying is more than your outstanding balance. Kindly pay the expected loan balance of "
.$prev_balance." KSh. Thank you.";
SendSMSTrait::sendSMS($message,"+254".$phone_number);

return json_encode(["success" => 1, "message" => "Kindly pay the expected balance"]);

} elseif ($balance == 0) {

$payment = new Repayment();
$payment->loan_id = $loan->id;
$payment->mpesa_repayment_id = $mpesa_repayment->id;
$payment->amount = $amount;
$payment->balance = $balance;
$payment->save();

$loan->loan_status = 2;
$loan->clearance_date = now();
$loan->save();

$phone_number = UniversalMethods::formatPhoneNumber($loan->phone);
$fname = $loan->fname;

$message = "Hi, "
. $fname. " Your loan payment of "
. $amount . " KSh has been received, Your loan is now fully settled. Thank you for using LINQ MOBILE ";
SendSMSTrait::sendSMS($message,"+254".$phone_number);

return json_encode(["success" => 0, "Thank you for paying your loan. Your loan is now completely paid"]);
} else {

$payment = new Repayment();
$payment->loan_id = $loan->id;
$payment->mpesa_repayment_id = $mpesa_repayment->id;
$payment->amount = $amount;
$payment->balance = $balance;
$payment->save();

$phone_number = UniversalMethods::formatPhoneNumber($loan->phone);
$fname = $loan->fname;

$message = "Hi, "
. $fname. " Your loan payment of "
. $amount . " KSh has been received. Your outstanding balance is "
.$balance." KSh.Pay your loan in time to increase your loan limit";
SendSMSTrait::sendSMS($message,"+254".$phone_number);

return json_encode(["success" => 2, "message" => "Thank you for paying your loan"]);
}*/


} else {

$stk_repayment_request->status = 3;
$stk_repayment_request->save();
}

return $request->Body['stkCallback']['ResultCode'];

}


/*

* Validation

* only accept payments of subscription amount multiples

*/


public function validation(Request $request)
{



$identifier = "C2B_VALIDATION_RAW";

$ip = $request->ip();

$callback = new MpesaCallBack();

$callback->ip = $ip;

$callback->content = $request->getContent();

$callback->caller = $identifier;

$callback->save();


$content = json_decode($request->getContent());

//get VALIDATION Details..........

$msisdn = $content->MSISDN;

$transaction_amount = $content->TransAmount;

$transaction_bill_ref_number = $content->BillRefNumber;//user_id

$first_name = $content->FirstName;

$middle_name = $content->MiddleName;

$last_name = $content->LastName;


//LOG THE DETAILS....

$identifier = "C2B_VALIDATION_DETAILS";

$detailed_content = "Names: " . $first_name . ", " . $middle_name . "," . $last_name . " | Phone: " . $msisdn . " | Amount: " . $transaction_amount;


$callback = new MpesaCallBack();

$ip = $request->ip();

$callback->ip = $ip;

$callback->content = $detailed_content;

$callback->caller = $identifier;

$callback->save();

$loanee = Loan::join('app_users', 'loans.user_id', '=', 'app_users.id')
->select('loans.*', 'app_users.phone_number as phone number',
'app_users.first_name as fname', 'app_users.second_name as sname',
'app_users.surname as surname')
->get();

//        if (!($loanee->where('phone_number','=',$content->BillRefNumber)->exists()))
//        {
//            $result_code = "C2B0012";
//
//            $result_description = "Invalid Account Number";
//        }else{


//initialize


/*

* check if user exists and amount is multiple of subscription amount

if(!$this->orderNumberFound($transaction_bill_ref_number,$transaction_amount))

*/
//
//        $subscription=Subscription::get()->last()['amount'];

if (!(AppUser::where('phone_number', '=', $content->BillRefNumber)->exists())) {

$result_code = "C2B00012";

$result_description = "Invalid Account number";

} elseif ($content->TransAmount % 100 !== 0) {
$result_code = "C2B00013";

$result_description = "Invalid Amount";
} else {
$result_code = "0";

$result_description = "Validation Service request accepted successfully";
}



$repay = new LoanRepaymentRequest();
$repay->loan_id = 1;
$repay->amount = 2;
$repay->status = 1;
$repay->save();
//        $repay = new LoanRepaymentRequest();
//        $repay->loan_id = $loan->id;
//        $repay->amount = $curl_post_data['Amount'];
//        $repay->status = 1;
//        $repay->save();


return $this->validationResponse($result_code, $result_description);

}


public function validationResponse($result_code, $result_description)
{

$result = json_encode(["ResultDesc" => $result_description, "ResultCode" => $result_code]);

$response = new Response();

$response->headers->set("Content-Type", "application/json; charset=utf-8");

$response->setContent($result);

return $response;

}


//Confirmation

public function confirmation(Request $request)
{

$identifier = "C2B_CONFIRMATION";
$identifier2 = "MPESA_VERIFICATION";


$callback = new MpesaCallBack();

$ip = $request->ip();

$callback->ip = $ip;

$callback->content = $request->getContent();

$callback->caller = $identifier;

$callback->save();

$phone_paying =$request->get('BillRefNumber');

if ($this->startsWith($phone_paying, "2547")) {

$pos = strpos($phone_paying, "2547");

if ($pos !== false) {

$phone_paying = substr_replace($phone_paying, "07", $pos, 4);

}


//$phone_paying=str_replace("0","254",$phone_paying);

}


//        $content = json_decode($request->getContent());

$loan = Loan::join('app_users', 'loans.user_id', '=', 'app_users.id')
->select('loans.*','app_users.phone_number as phone_number','app_users.id as user')
->where('app_users.phone_number', $phone_paying)
->where(function ($q){
$q ->where('loans.loan_status', 1)
->orWhere('loans.loan_status', 3);
})
->first();



if ($loan == null){

$verify = new VerificationMpesaCall();
$verify->ip = $ip;
$verify->content = $request->getContent();
$verify->caller = $identifier2;
$verify->save();

$user = AppUser::where('phone_number',$phone_paying)->first();

if (strcasecmp($user->first_name, $request->get('FirstName')) == 0 && (strcasecmp($user->surname, $request->get('LastName')) == 0
|| strcasecmp($user->surname, $request->get('MiddleName')) == 0 || strcasecmp($user->middle_name, $request->get('LastName')) == 0 ||
strcasecmp($user->middle_name, $request->get('MiddleName')) == 0)) {

$mpesa_verification = new MpesaVerification();
$mpesa_verification->first_name = $request->get('FirstName');
$mpesa_verification->last_name = $request->get('MiddleName');
if($request->get('LastName') != null){
$mpesa_verification->middle_name = $request->get('LastName');
}
$mpesa_verification->user_id = $user->id;
$mpesa_verification->verification_status = 1;
$mpesa_verification->save();


$ver_identifier = 'VERIFICATION_PAYMENT_DETAILS_MATCH';

$verification_payment = new VerificationMpesaPayment();
$verification_payment->identifier = $ver_identifier;
$verification_payment->amount = $request->get('TransAmount');
$verification_payment->msisdn = $request->get('MSISDN');
$verification_payment->transaction_id = $request->get('TransID');
$verification_payment->first_name = $request->get('FirstName');
$verification_payment->middle_name = $request->get('MiddleName');
$verification_payment->last_name = $request->get('LastName');
$verification_payment->bill_reference = $request->get('BillRefNumber');
$verification_payment->balance = $request->get('OrgAccountBalance');
$verification_payment->time = $request->get('TransTime');
$verification_payment->save();

$phone_number = UniversalMethods::formatPhoneNumber($user->phone_number);
$fname = $user->first_name;

$message = "Hi, "
. $fname. ". Your Mpesa details have been verified successfully. We will send you an sms to confirm your eligibility";
SendSMSTrait::sendSMS($message,"+254".$phone_number);


}else{

$verify = new VerificationMpesaCall();
$verify->ip = $ip;
$verify->content = $request->getContent();
$verify->caller = $identifier2;
$verify->save();


$mpesa_verification = new MpesaVerification();
$mpesa_verification->first_name = $request->get('FirstName');
$mpesa_verification->last_name = $request->get('MiddleName');
if($request->get('LastName') != null){
$mpesa_verification->middle_name = $request->get('LastName');
}
$mpesa_verification->user_id = $user->id;
$mpesa_verification->verification_status = 2;
$mpesa_verification->save();

$ver_identifier = 'VERIFICATION_PAYMENT_DETAILS_DO_NOT_MATCH';

$verification_payment = new VerificationMpesaPayment();
$verification_payment->identifier = $ver_identifier;
$verification_payment->amount = $request->get('TransAmount');
$verification_payment->msisdn = $request->get('MSISDN');
$verification_payment->transaction_id = $request->get('TransID');
$verification_payment->first_name = $request->get('FirstName');
$verification_payment->middle_name = $request->get('MiddleName');
$verification_payment->last_name = $request->get('LastName');
$verification_payment->bill_reference = $request->get('BillRefNumber');
$verification_payment->balance = $request->get('OrgAccountBalance');
$verification_payment->time = $request->get('TransTime');
$verification_payment->save();

$user->status = 3;
$user->save();

$phone_number = UniversalMethods::formatPhoneNumber($user->phone_number);
$fname = $user->first_name;

$message = "Hi, "
. $fname. ". Your Mpesa details do not match your ID details. Contact us for further enquiries.";
SendSMSTrait::sendSMS($message,"+254".$phone_number);
}

return response()->json([
'code'=>0,
'message'=> 'Mpesa Verification'
]);

}else{

$loan_request = LoanRepaymentRequest::where('loan_id', $loan->id)
->where('amount', $request->get('TransAmount'))
->where('status',1)
->orderby('id', 'desc')
->first();

if ($loan_request == null){
$lrequest = new LoanRepaymentRequest();
$lrequest->loan_id = $loan->id;
$lrequest->amount = $request->get('TransAmount');
$lrequest->status = 2;
$lrequest->save();
}else{
$loan_request->status = 2;
$loan_request->save();
}

//        dd($callback);

//get Confirmation Details..........



//Update Organisation balance table


CashFlow::create([
'identifier' => $identifier,
'amount' => $request->get('TransAmount'),
'paybill_balance' => $request->get('OrgAccountBalance')
]);

$paybillbalance = new PayBillBalance();
$paybillbalance->transaction_id = $request->get('TransID');
$paybillbalance->transaction_amount = $request->get('TransAmount');
$paybillbalance->balance_amount = $request->get('OrgAccountBalance');
$paybillbalance->save();

$paybillbalance->time = $request->get('TransTime');

/*register the payment by the user
record the subscription
1 month=30 days
*/

//        $payment = new Payment();
//
//        $payment->user_id = $loan->user_id;
//
//        $payment->amount = $transaction_amount;
//
//        $payment->phone_number = $msisdn;
//
//
//        $payment->transaction_id = $transaction_id;
//
//        $payment->first_name = $first_name;
//
//        $payment->middle_name = $middle_name . ", " . $last_name;
//
//        $payment->transaction_time = $transaction_time;
//
//        $payment->transaction_bill_ref_number = $transaction_bill_ref_number; //Order Number
//
//        $payment->save();


$mpesa_repayment = new MpesaRepaymnt();
$mpesa_repayment->identifier = $identifier;
$mpesa_repayment->amount = $request->get('TransAmount');
$mpesa_repayment->msisdn = $request->get('MSISDN');
$mpesa_repayment->transaction_id = $request->get('TransID');
$mpesa_repayment->first_name = $request->get('FirstName');
$mpesa_repayment->middle_name = $request->get('MiddleName');
$mpesa_repayment->last_name = $request->get('LastName');
$mpesa_repayment->bill_reference = $request->get('BillRefNumber');
$mpesa_repayment->balance = $request->get('OrgAccountBalance');
$mpesa_repayment->time = $request->get('TransTime');
$mpesa_repayment->save();

$rep = Repayment::where('loan_id', $loan->id)
->orderby('id', 'desc')->first();
$total_loan = $loan->principal_amount + $loan->interest_amount;

if ($rep == null){
$prev_balance = $total_loan;
}else{
$prev_balance = $rep->balance;
}

$result_description = "C2B Payment Transaction $request->get('TransID') result received.";

$result_code = "0";


$result = json_encode(["ResultDesc" => $result_description, "ResultCode" => $result_code]);


$response = new Response();

$response->headers->set("Content-Type", "text/xml; charset=utf-8");

$response->setContent($result);

if ($rep === null) {
$balance = $total_loan - $request->get('TransAmount');
} else {
$balance = $prev_balance - $request->get('TransAmount');
}

if ($balance < 0) {

$phone_number = UniversalMethods::formatPhoneNumber($loan->phone_number);
$fname = $loan->fname;

$message = "Hi, "
. $request->get('FirstName'). " Your loan payment of "
. $request->get('TransAmount') . " Ksh has been received, Your loan is now fully settled. Thank you for using LINQ MOBILE ";
SendSMSTrait::sendSMS($message,"+254".$phone_number);

return json_encode(["success" => 1, "message" => "Kindly pay the expected balance"]);

} elseif ($balance == 0) {

$payment = new Repayment();

$payment->loan_id = $loan->id;

$payment->mpesa_repayment_id = $mpesa_repayment->id;

$payment->amount = $request->get('TransAmount');

$payment->balance = $balance;
$payment->save();

$loan->loan_status = 2;
$loan->clearance_date = now();
$loan->save();

$phone_number = UniversalMethods::formatPhoneNumber($loan->phone_number);
$fname = $loan->fname;

$message = "Hi, "
. $request->get('FirstName'). " Your loan payment of "
. $request->get('TransAmount') . " Ksh has been received, Your loan is now fully settled. Thank you for using LINQ MOBILE ";
SendSMSTrait::sendSMS($message,"+254".$phone_number);

return json_encode(["success" => 0, "Thank you for paying your loan. Your loan is now completely paid"]);
} else {

$payment = new Repayment();

$payment->loan_id = $loan->id;

$payment->mpesa_repayment_id = $mpesa_repayment->id;

$payment->amount = $request->get('TransAmount');
$payment->balance = $balance;

$payment->save();

$phone_number = UniversalMethods::formatPhoneNumber($loan->phone_number);
$fname = $loan->fname;

$message = "Hi, "
. $request->get('FirstName'). " Your loan payment of "
. $request->get('TransAmount') . " Ksh has been received. Your outstanding balance is "
.$balance." KSh.Pay your loan in time to increase your loan limit";
SendSMSTrait::sendSMS($message,"+254".$phone_number);

return json_encode(["success" => 2, "message" => "Thank you for paying your loan"]);
}

}

//        update loan repayments request status


}

public function getAccessToken()
{

//Variables specific to this application

$consumer_key = "t86V46LwTBzhBuqM2v8Wqlhof63uagx9"; //Get these two from DARAJA Platform


$consumer_secret = "DC47sjXXlP5MBATB";

$credentials = base64_encode($consumer_key . ":" . $consumer_secret);

//START CURL

$url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
//        $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

//        $url = 'https://sendbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
//        $url = 'https://sendbo.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $url);

curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));

curl_setopt($curl, CURLOPT_HEADER, false);//Make it not return headers...true retirns header

curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//MAGIC..

$curl_response = curl_exec($curl);

//        dd($curl_response);

$access_token = json_decode($curl_response);

return $access_token->access_token;

}

function startsWith($haystack, $needle)

{

$length = strlen($needle);

return (substr($haystack, 0, $length) === $needle);

}

//    public function forMyTests()
//
//    {
//
//        $lastrecord=Subscription::get()->last()['amount'];
//
//        $last=$lastrecord%100;//$a % $b
//
//
//
//        $prev_subscription=UserSubscriptions::where('user_id','=',11)->get()->last();
//
//        $prev_since_when=$prev_subscription['since_when'];
//
//        $prev_prd=30*$prev_subscription['months'];
//
//        $new_since_when=date('Y-m-d', strtotime($prev_since_when. ' + '.$prev_prd.' days'));
//
//        dd($new_since_when);
//
//    }

}
