<?php

namespace App\Http\Controllers\Api\V1;

ini_set('memory_limit', '-1');

use App\Models\Order;
use App\Models\Shift;
use App\Library\Payer;
use App\Traits\Payment;
use App\Library\Receiver;
use App\Models\DeliveryMan;
use App\Models\Notification;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\DeliveryHistory;
use App\Models\ProvideDMEarning;
use App\Models\UserNotification;
use App\Models\WithdrawalMethod;
use App\CentralLogics\OrderLogic;
use App\Models\DeliveryManWallet;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\DisbursementDetails;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use App\Library\Payment as PaymentInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\DisbursementWithdrawalMethod;

class DeliverymanController extends Controller
{

    public function get_profile(Request $request)
    {
        $dm = DeliveryMan::with(['rating','userinfo','dm_shift'])->where(['auth_token' => $request['token']])->first();
        $min_amount_to_pay_dm = BusinessSetting::where('key' , 'min_amount_to_pay_dm')->first()->value ?? 0;

        $dm['avg_rating'] = (double)($dm?->rating[0]?->average ?? 0);
        $dm['rating_count'] = (double)($dm?->rating[0]?->rating_count ?? 0);
        $dm['order_count'] =(integer)$dm?->orders->count();
        $dm['todays_order_count'] =(integer)$dm->todaysorders->count();
        $dm['this_week_order_count'] =(integer)$dm->this_week_orders->count();
        $dm['member_since_days'] =(integer)$dm->created_at->diffInDays();

        $dm['cash_in_hands'] =(float) ($dm?->wallet?->collected_cash ?? 0);
        $dm['balance'] = (float) ($dm?->wallet?->total_earning - $dm?->wallet?->total_withdrawn  ?? 0);

        $dm['total_withdrawn'] = (float) ($dm?->wallet?->total_withdrawn ?? 0);
        $dm['total_earning'] = (float) ($dm?->wallet?->total_earning ?? 0);
        $dm['withdraw_able_balance'] =(float)( $dm['balance'] - $dm?->wallet?->collected_cash > 0 ? abs($dm['balance'] - $dm?->wallet?->collected_cash ): 0 );
        $dm['Payable_Balance'] =(float)(  $dm?->wallet?->collected_cash ?? 0 );
        $dm['pending_withdraw'] =(float)(  $dm?->wallet?->pending_withdraw ?? 0 );
        $over_flow_balance = $dm['balance'] - $dm?->wallet?->collected_cash ;
        $dm['adjust_able'] = false;
        if(isset($dm?->wallet) && (($over_flow_balance > 0 && $dm?->wallet?->collected_cash > 0 ) || ($dm?->wallet?->collected_cash != 0 && $dm['balance'] !=  0)) ){
            $dm['adjust_able'] = true;
        }
        if( isset($dm?->wallet) &&  $over_flow_balance == $dm['balance']  ){
            $dm['adjust_able'] = false;
        }
        if( isset($dm?->wallet) &&  ($dm?->wallet?->collected_cash ==  0  ||    ( ($dm?->wallet?->total_earning -($dm?->wallet?->total_withdrawn + $dm?->wallet?->pending_withdraw) ) <= 0 ))){
            $dm['adjust_able'] = false;
        }

        $dm['show_pay_now_button'] = false;
        $digital_payment = Helpers::get_business_settings('digital_payment');
        if ($min_amount_to_pay_dm <= $dm?->wallet?->collected_cash && $digital_payment['status'] == 1 ){
            $dm['show_pay_now_button'] = true;
        }


        $Payable_Balance =  $dm?->wallet?->collected_cash > 0 ? 1: 0;
        $cash_in_hand_overflow=  BusinessSetting::where('key' ,'cash_in_hand_overflow_delivery_man')->first()?->value;
        $cash_in_hand_overflow_delivery_man =  BusinessSetting::where('key' ,'dm_max_cash_in_hand')->first()?->value;
        $val=  $cash_in_hand_overflow_delivery_man - (($cash_in_hand_overflow_delivery_man * 10)/100);
        $dm['over_flow_warning'] = false;

        if($Payable_Balance == 1 &&  $cash_in_hand_overflow &&  $over_flow_balance < 0 &&  $val <=  abs($dm?->wallet?->collected_cash)){

            $dm['over_flow_warning'] = true;
        }

        $dm['over_flow_block_warning'] = false;
        if ($Payable_Balance == 1 &&  $cash_in_hand_overflow &&  $over_flow_balance < 0 &&  $cash_in_hand_overflow_delivery_man < abs($dm?->wallet?->collected_cash)){
            $dm['over_flow_block_warning'] = true;
        }


        $dm['todays_earning'] =(float)($dm?->todays_earning()?->sum('original_delivery_charge') + $dm?->todays_earning()?->sum('dm_tips'));
        $dm['this_week_earning'] =(float)($dm?->this_week_earning()?->sum('original_delivery_charge') + $dm?->this_week_earning()?->sum('dm_tips'));
        $dm['this_month_earning'] =(float)($dm?->this_month_earning()?->sum('original_delivery_charge') + $dm?->this_month_earning()?->sum('dm_tips'));
        $dm['total_incentive_earning']= (float) ($dm?->incentives()?->where('status','approved')->sum('incentive'));
        $dm['incentive_list']= $dm?->zone?->incentives ?? [];
        $dm['shift_name']= $dm?->dm_shift?->name ?? null;
        $dm['shift_start_time']= $dm?->dm_shift?->start_time ?? null;
        $dm['shift_end_time']= $dm?->dm_shift?->end_time ?? null;
        unset($dm['dm_shift']);
        unset($dm['zone']);
        unset($dm['orders']);
        unset($dm['rating']);
        unset($dm['todaysorders']);
        unset($dm['this_week_orders']);
        unset($dm['wallet']);
        return response()->json($dm, 200);
    }

    public function update_profile(Request $request)
    {
        $dm = DeliveryMan::with(['rating'])->where(['auth_token' => $request['token']])->first();
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:delivery_men,email,'.$dm->id,
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],

            'image' => 'nullable|max:2048',
        ], [
            'f_name.required' => translate('First name is required!'),
            'l_name.required' => translate('Last name is required!'),
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
            'password.custom' => translate('The password cannot contain white spaces.'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->has('image')) {
            $imageName = Helpers::update(dir:'delivery-man/', old_image:$dm->image,format: 'png',image: $request->file('image'));
        } else {
            $imageName = $dm->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $dm->password;
        }

        $dm->vehicle_id = $request?->vehicle_id ??  $dm?->vehicle_id ?? null;

        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->image = $imageName;
        $dm->password = $pass;
        $dm->updated_at = now();
        $dm->save();

        if($dm->userinfo) {
            $userinfo = $dm->userinfo;
            $userinfo->f_name = $request->f_name;
            $userinfo->l_name = $request->l_name;
            $userinfo->email = $request->email;
            $userinfo->image = $imageName;
            $userinfo->save();
        }
        return response()->json(['message' => translate('successfully updated')], 200);
    }

    public function activeStatus(Request $request)
    {
        // $shift_id =$request->shift_id ?? Null;
        $dm = DeliveryMan::with(['rating'])->where(['auth_token' => $request['token']])->first();
        $dm->active = $dm->active?0:1;
        $dm->shift_id =$request->shift_id ?? $dm->shift_id ??  null;
        $dm->save();
        Helpers::set_time_log(user_id:$dm->id,date: date('Y-m-d') ,online: ($dm->active ? now() : null),offline: ($dm->active ? null : now()) , shift_id:$dm->shift_id );
        return response()->json(['message' => translate('messages.active_status_updated')], 200);
    }

    public function get_current_orders(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $orders = Order::with(['customer', 'restaurant'])
        ->whereIn('order_status', ['accepted','confirmed','pending', 'processing', 'picked_up', 'handover'])
        ->where(['delivery_man_id' => $dm['id']])
        ->orderBy('accepted')
        ->orderBy('schedule_at', 'desc')
        ->Notpos()
        ->get();
        $orders= Helpers::order_data_formatting($orders, true);
        return response()->json($orders, 200);
    }

    public function get_latest_orders(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $orders = Order::with(['customer', 'restaurant']);

        if($dm->type == 'zone_wise')
            {
                $orders = $orders
                ->whereHas('restaurant', function($q) use($dm){
                    $q->where('restaurant_model','subscription')->where('zone_id', $dm->zone_id)->whereHas('restaurant_sub', function($q1){
                        $q1->where('self_delivery', 0);
                    });
                })
                ->orWhereHas('restaurant', function($qu) use($dm) {
                    $qu->where('restaurant_model','commission')->where('zone_id', $dm->zone_id)->where('self_delivery_system', 0);
                });
            }
            else
            {
                $orders = $orders->where('restaurant_id', $dm->restaurant_id);
            }

        if(config('order_confirmation_model') == 'deliveryman' && $dm->type == 'zone_wise')
        {
            $orders = $orders->whereIn('order_status', ['pending', 'confirmed','processing','handover']);
        }
        else
        {
            $orders = $orders->where(function($query){
                $query->where(function($query){
                    $query->where('order_status', 'pending')->whereNotNull('subscription_id');
                })->orWhereIn('order_status', ['confirmed','processing','handover']);
            });
        }

        if(isset($dm->vehicle_id )){
            $orders = $orders->where('vehicle_id',$dm->vehicle_id);
        }
        $orders = $orders->delivery()
        ->OrderScheduledIn(30)
        ->NotDigitalOrder()
        ->whereNull('delivery_man_id')
        ->orderBy('schedule_at', 'desc')
        ->Notpos()
        ->get();
        $orders= Helpers::order_data_formatting($orders, true);
        return response()->json($orders, 200);
    }

    public function accept_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm=DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::where('id', $request['order_id'])
        ->whereNull('delivery_man_id')
        ->Notpos()
        ->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.can_not_accept')]
                ]
            ], 404);
        }
        if($dm->current_orders >= config('dm_maximum_orders'))
        {
            return response()->json([
                'errors'=>[
                    ['code' => 'dm_maximum_order_exceed', 'message'=> translate('messages.dm_maximum_order_exceed_warning')]
                ]
            ], 405);
        }

        $cash_in_hand =$dm?->wallet?->collected_cash ?? 0;


        $dm_max_cash_in_hand=  BusinessSetting::where('key','dm_max_cash_in_hand')->first()?->value ?? 0;

        if($order->payment_method == "cash_on_delivery" && (($cash_in_hand+$order->order_amount) >= $dm_max_cash_in_hand)){
            return response()->json(['errors' => Helpers::error_formater('dm_max_cash_in_hand',translate('delivery man max cash in hand exceeds'))], 203);
        }

        $order->order_status = in_array($order->order_status, ['pending', 'confirmed'])?'accepted':$order->order_status;
        $order->delivery_man_id = $dm->id;
        $order->accepted = now();
        $order->save();

        $dm->current_orders = $dm->current_orders+1;
        $dm->save();

        $dm->increment('assigned_order_count');

        $fcm_token= ($order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token) ?? null;


        $value = Helpers::text_variable_data_format(value:Helpers::order_status_update_message('accepted',$order->customer?$order?->customer?->current_language_key:'en'),restaurant_name:$order->restaurant?->name,order_id:$order->id,user_name:"{$order?->customer?->f_name} {$order?->customer?->l_name}",delivery_man_name:"{$order->delivery_man?->f_name} {$order->delivery_man?->l_name}");


        OrderLogic::update_subscription_log($order);
        try {
            $customer_push_notification_status=Helpers::getNotificationStatusData('customer','customer_order_notification');
            if(  $customer_push_notification_status?->push_notification_status  == 'active' && $value && $fcm_token)
            {
                $data = [
                    'title' =>translate('messages.order_push_title'),
                    'description' => $value,
                    'order_id' => $order['id'],
                    'image' => '',
                    'type'=> 'order_status'
                ];
                Helpers::send_push_notif_to_device($fcm_token, $data);
            }

        } catch (\Exception $e) {
            info($e->getMessage());
        }

        return response()->json(['message' => translate('Order accepted successfully')], 200);

    }

    public function record_location_data(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        DeliveryHistory::updateOrCreate(['delivery_man_id' => $dm['id']], [
            'longitude' => $request['longitude'],
            'latitude' => $request['latitude'],
            'time' => now(),
            'location' => $request['location'],
            'created_at' => now(),
            'updated_at' => now()
            ]);

        return response()->json(['message' => translate('location recorded')], 200);
    }

    public function get_order_history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $history = DeliveryHistory::where(['order_id' => $request['order_id'], 'delivery_man_id' => $dm['id']])->get();
        return response()->json($history, 200);
    }

    public function update_order_status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => 'required|in:confirmed,canceled,picked_up,delivered',
            'reason' =>'required_if:status,canceled',
            'order_proof' =>'nullable|array|max:5',
        ]);

        $validator->sometimes('otp', 'required', function ($request) {
            return (Config::get('order_delivery_verification')==1 && $request['status']=='delivered');
        });

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::where('id', $request['order_id'])->with(['subscription_logs','details'])->first();
        if(!isset($order)){
            return response()->json([
                'errors' => [
                    ['code' => 'order-not-found', 'message' => translate('messages.order_not_found')]
                ]
            ], 403);
        }

        if($request['status'] =="confirmed" && config('order_confirmation_model') == 'restaurant')
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order-confirmation-model', 'message' => translate('messages.order_confirmation_warning')]
                ]
            ], 403);
        }

        if($request['status'] == 'canceled' && !config('canceled_by_deliveryman'))
        {
            return response()->json([
                'errors' => [
                    ['code' => 'status', 'message' => translate('messages.you_can_not_cancel_a_order')]
                ]
            ], 403);
        }

        if(isset($order->confirmed ) && $request['status'] == 'canceled')
        {
            return response()->json([
                'errors' => [
                    ['code' => 'delivery-man', 'message' => translate('messages.order_can_not_cancle_after_confirm')]
                ]
            ], 403);
        }

        if(Config::get('order_delivery_verification')==1 && $request['status']=='delivered' && $order->otp != $request['otp'])
        {
            return response()->json([
                'errors' => [
                    ['code' => 'otp', 'message' => translate('Not matched')]
                ]
            ], 406);
        }
        if ($request->status == 'delivered' || isset($order->subscription_id))
        {

            if(isset($order->subscription_id) && count($order->subscription_logs) == 0 ){
                return response()->json([
                    'errors' => [
                        ['code' => 'order-subscription', 'message' => translate('messages.You_Can_Not_Delivered_This_Subscription_order_Before_Schedule')]
                    ]
                ], 403);
            }

            if($order->transaction == null)
            {
                $unpaid_payment = OrderPayment::where('payment_status','unpaid')->where('order_id',$order->id)->first();
                $pay_method = 'digital_payment';
                if($unpaid_payment && $unpaid_payment->payment_method == 'cash_on_delivery'){
                    $pay_method = 'cash_on_delivery';
                }
                $reveived_by = ($order->payment_method == 'cash_on_delivery' || $pay_method == 'cash_on_delivery') ?($dm->type != 'zone_wise'?'restaurant':'deliveryman'):'admin';

                if(OrderLogic::create_transaction(order:$order,received_by: $reveived_by, status:null))
                {
                    $order->payment_status = 'paid';
                }
                else
                {
                    return response()->json([
                        'errors' => [
                            ['code' => 'error', 'message' => translate('messages.faield_to_create_order_transaction')]
                        ]
                    ], 406);
                }
            }
            if($order->transaction)
            {
                $order->transaction->update(['delivery_man_id'=>$dm->id]);
            }

            $order?->details->each(function($item, $key){
                if($item->food)
                {
                    $item->food->increment('order_count');
                }
            });
            $order?->customer?->increment('order_count') ?? '';

            $dm->current_orders = $dm->current_orders>1?$dm->current_orders-1:0;
            $dm->save();

            $dm->increment('order_count');
            $order->restaurant->increment('order_count');


            $img_names = [];
            $images = [];
            if (!empty($request->file('order_proof'))) {
                foreach ($request->order_proof as $img) {
                    $image_name = Helpers::upload('order/', 'png', $img);
                    array_push($img_names, ['img'=>$image_name, 'storage'=> Helpers::getDisk()]);
                }
                $images = $img_names;
            }
            $order->order_proof = json_encode($images);


            OrderLogic::update_unpaid_order_payment(order_id:$order->id, payment_method:$order->payment_method);


        }
        else if($request->status == 'canceled')
        {
            if($order->delivery_man)
            {
                $dm = $order->delivery_man;
                $dm->current_orders = $dm->current_orders>1?$dm->current_orders-1:0;
                $dm->save();
            }


            if(!isset($order->confirmed) && isset($order->subscription_id)){
                $order?->subscription()?->update(['status' => 'canceled']);
                if($order?->subscription?->log){
                    $order->subscription->log()->update([
                        'order_status' => $request->status,
                        'canceled' => now(),
                        ]);
                }
            }

            $order->cancellation_reason = $request->reason;
            $order->canceled_by = 'deliveryman';


            Helpers::decreaseSellCount(order_details:$order->details);

        }

        if($request->status == 'confirmed' &&  $order->delivery_man_id == null){
            $order->delivery_man_id = $dm->id;
        }
        // dd($request['status']);
        $order->order_status = $request['status'];
        $order[$request['status']] = now();
        $order->save();
        try {
            Helpers::send_order_notification($order);
        } catch (\Exception $th) {
            info($th->getMessage());
        }

        OrderLogic::update_subscription_log($order);
        return response()->json(['message' => translate('Status updated')], 200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        // OrderLogic::create_subscription_log($request->order_id);
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::with(['details'])->where('id',$request['order_id'])->where(function($query) use($dm){
            $query->whereNull('delivery_man_id')
                ->orWhere('delivery_man_id', $dm['id']);
        })->Notpos()->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }
        $details = Helpers::order_details_data_formatting($order->details);
        return response()->json($details, 200);
    }

    public function get_order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $order = Order::with(['customer', 'restaurant','details','payments'])->where('id', $request['order_id'])
        ->where(function($q)use($dm){
            $q->where('delivery_man_id', $dm['id'])
            ->orWhereHas('subscription_logs',function($query)use($dm){
                $query->where('delivery_man_id', $dm['id'])->whereIn('order_status', ['delivered','canceled','refund_requested','refunded','refund_request_canceled','failed']);
            });
        })
        ->Notpos()->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }
        return response()->json(Helpers::order_data_formatting($order), 200);
    }

    public function get_all_orders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();


        $paginator = Order::with(['customer', 'restaurant'])
        ->whereIn('order_status', ['delivered','canceled','refund_requested','refunded','refund_request_canceled','failed'])
        ->where(function($q)use($dm){
            $q->where('delivery_man_id', $dm['id'])
            ->orWhereHas('subscription_logs',function($query)use($dm){
                $query->where('delivery_man_id', $dm['id'])->whereIn('order_status', ['delivered','canceled','refund_requested','refunded','refund_request_canceled','failed']);
            });
        })

        ->orderBy('schedule_at', 'desc')
        ->Notpos()
        ->paginate($request['limit'], ['*'], 'page', $request['offset']);
        $orders= Helpers::order_data_formatting($paginator->items(), true);
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'orders' => $orders
        ];
        return response()->json($data, 200);
    }

    public function get_last_location(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $last_data = DeliveryHistory::whereHas('delivery_man.orders', function($query) use($request){
            return $query->where('id',$request->order_id);
        })->latest()->first();
        return response()->json($last_data, 200);
    }

    public function order_payment_status_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => 'required|in:paid'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if (Order::where(['delivery_man_id' => $dm['id'], 'id' => $request['order_id']])->Notpos()->first()) {
            Order::where(['delivery_man_id' => $dm['id'], 'id' => $request['order_id']])->update([
                'payment_status' => $request['status']
            ]);
            return response()->json(['message' => translate('Payment status updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('not found')]
            ]
        ], 404);
    }

    public function update_fcm_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        DeliveryMan::where(['id' => $dm['id']])->update([
            'fcm_token' => $request['fcm_token']
        ]);

        return response()->json(['message'=>'successfully updated!'], 200);
    }

    public function get_notifications(Request $request){

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $notifications = Notification::active()->where(function($q) use($dm){
                $q->whereNull('zone_id')->orWhere('zone_id', $dm->zone_id);
            })->where('tergat', 'deliveryman')->where('created_at', '>=', \Carbon\Carbon::today()->subDays(7))->get();

        $user_notifications = UserNotification::where('delivery_man_id', $dm->id)->where('created_at', '>=', \Carbon\Carbon::today()->subDays(7))->get();

        $notifications->append('data');

        $notifications =  $notifications->merge($user_notifications);
        try {
            return response()->json($notifications, 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function remove_account(Request $request)
    {
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        if(Order::where('delivery_man_id', $dm->id)->whereIn('order_status', ['pending','accepted','confirmed','processing','handover','picked_up'])->count())
        {
            return response()->json(['errors'=>[['code'=>'on-going', 'message'=>translate('messages.user_account_delete_warning')]]],203);
        }

        if($dm->wallet && $dm->wallet->collected_cash > 0)
        {
            return response()->json(['errors'=>[['code'=>'on-going', 'message'=>translate('messages.user_account_wallet_delete_warning')]]],203);
        }

        Helpers::check_and_delete('delivery-man/' , $dm['image']);

        foreach (json_decode($dm['identity_image'], true) as $img) {
            Helpers::check_and_delete('delivery-man/' , $img);
        }
        if($dm->userinfo){
            $dm->userinfo->delete();
        }

        $dm->delete();
        return response()->json([]);
    }

    public function dm_shift(Request $request){
        $shift= Shift::where('status',1)
            ->latest()
            ->get();
            return response()->json($shift, 200);
        }
    // public function assign_vehicle(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'vehicle_id' => 'required',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(['errors' => Helpers::error_processor($validator)], 403);
    //     }

    //     $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
    //     $dm->vehicle_id = $request['vehicle_id'];
    //     $dm->save();
    //     return response()->json(['message'=>'successfully updated!'], 200);
    // }

    public function send_order_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $order = Order::where(['id' => $request['order_id'], 'delivery_man_id' => $dm['id']]);
        if(config('order_confirmation_model') == 'deliveryman' && $dm->type == 'zone_wise')
        {
            $order = $order->whereIn('order_status', ['pending', 'confirmed','processing','handover','picked_up']);
        }
        else
        {
            $order = $order->where(function($query){
                $query->whereIn('order_status', ['confirmed','processing','handover','picked_up']);
            });
        }
        $order = $order->Notpos()->first();
        if(!$order)
        {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('messages.not_found')]
                ]
            ], 404);
        }
        $value = translate('your_order_is_ready_to_be_delivered,_plesae_share_your_otp_with_delivery_man.').' '.translate('otp:').$order->otp.', '.translate('order_id:').$order->id;
        try {
            $fcm_token= ($order->is_guest == 0 ? $order?->customer?->cm_firebase_token : $order?->guest?->fcm_token) ?? null;
            $customer_push_notification_status=Helpers::getNotificationStatusData('customer','customer_delivery_verification');
            if ($customer_push_notification_status?->push_notification_status  == 'active' && $value && $fcm_token) {
                $data = [
                    'title' => translate('messages.order_ready_to_be_delivered'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                    'order_status' => $order->order_status,
                ];
                Helpers::send_push_notif_to_device($order->customer->cm_firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $order->user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json(['message' => translate('messages.push_notification_faild')], 403);
        }
        return response()->json([], 200);
    }


    Public function make_payment(Request $request){
        $validator = Validator::make($request->all(), [
            'payment_gateway' => 'required',
            'amount' => 'required|numeric|min:.001',
            'callback' => 'required',
            'token' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $digital_payment = Helpers::get_business_settings('digital_payment');
        if($digital_payment['status'] == 0){
            return response()->json(['errors' => ['message' => 'digital_payment_is_disable']], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrfail();

        $payer = new Payer(
            $dm->f_name ,
            $dm->email,
            $dm->phone,
            ''
        );
        $additional_data = [
            'business_name' => BusinessSetting::where(['key'=>'business_name'])->first()?->value,
            'business_logo' => dynamicStorage('storage/app/public/business') . '/' .BusinessSetting::where(['key' => 'logo'])->first()?->value
        ];
        $payment_info = new PaymentInfo(
            success_hook: 'collect_cash_success',
            failure_hook: 'collect_cash_fail',
            currency_code: Helpers::currency_code(),
            payment_method: $request->payment_gateway,
            payment_platform: 'app',
            payer_id: $dm->id,
            receiver_id: '100',
            additional_data:  $additional_data,
            payment_amount: $request->amount ,
            external_redirect_link: $request->has('callback')?$request['callback']:session('callback'),
            attribute: 'deliveryman_collect_cash_payments',
            attribute_id: $dm->id,
        );

        $receiver_info = new Receiver('Admin','example.png');
        $redirect_link = Payment::generate_link($payer, $payment_info, $receiver_info);

        $data = [
            'redirect_link' => $redirect_link,
        ];
        return response()->json($data, 200);

    }


    public function make_wallet_adjustment(Request $request){

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrfail();
        $wallet = DeliveryManWallet::firstOrNew(
            ['delivery_man_id' =>$dm->id]
        );
        $wallet_earning =  $wallet->total_earning -($wallet->total_withdrawn + $wallet->pending_withdraw);
        $adj_amount =  $wallet->collected_cash - $wallet_earning;

        if($wallet->collected_cash == 0 || $wallet_earning == 0 ){
            return response()->json(['message' => translate('messages.Already_Adjusted')], 201);
        }

        if($adj_amount > 0 ){
        $wallet->total_withdrawn =  $wallet->total_withdrawn + $wallet_earning ;
        $wallet->collected_cash =   $wallet->collected_cash - $wallet_earning ;

        $data = [
            'delivery_man_id' => $dm->id,
            'amount' => $wallet_earning,
            'ref' => "delivery_man_wallet_adjustment_partial",
            'method' => "adjustment",
            'created_at' => now(),
            'updated_at' => now()
        ];

    } else{
        $data = [
            'delivery_man_id' => $dm->id,
            'amount' => $wallet->collected_cash ,
            'ref' => "delivery_man_wallet_adjustment_full",
            'method' => "adjustment",
            'created_at' => now(),
            'updated_at' => now()
        ];
        $wallet->total_withdrawn =  $wallet->total_withdrawn + $wallet->collected_cash ;
        $wallet->collected_cash =   0;

    }

    $wallet->save();
    DB::table('provide_d_m_earnings')->insert($data);

    return response()->json(['message' => translate('messages.Delivery_man_wallet_adjustment_successfull')], 200);
    }

    public function withdraw_method_list(){
        $wi=WithdrawalMethod::where('is_active',1)->get();
        return response()->json($wi,200);
    }

    public function get_disbursement_withdrawal_methods(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $key = explode(' ', $request['search']);
        $paginator = DisbursementWithdrawalMethod::where('delivery_man_id', $dm['id'])
            ->when( isset($key) , function($query) use($key){
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('method_name', 'like', "%{$value}%");
                    }
                });
            }
            )
            ->latest()
            ->paginate($request['limit'], ['*'], 'page', $request['offset']);

            $datas =[];
            foreach ($paginator->items() as $k => $v) {
                $userInputs=[];
                foreach(json_decode($v->method_fields,true)as $key => $value){
                    $userInput = [
                        'user_input' => $key,
                        'user_data' => $value,
                        ];
                        $userInputs[] = $userInput;
                    }
                    $v['method_fields'] = $userInputs;
                    $datas[] = $v;
                }

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'methods' => $datas
        ];
        return response()->json($data, 200);
    }

    public function disbursement_withdrawal_method_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdraw_method_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $method = WithdrawalMethod::find($request['withdraw_method_id']);
        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $method_data = [];
        foreach ($fields as $field) {
            if(key_exists($field, $values)) {
                $method_data[$field] = $values[$field];
            }
        }

        $data = [
            'delivery_man_id' => $dm['id'],
            'withdrawal_method_id' => $method['id'],
            'method_name' => $method['method_name'],
            'method_fields' => json_encode($method_data),
            'is_default' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];

        DB::table('disbursement_withdrawal_methods')->insert($data);

        return response()->json(['message'=> translate('successfully added')], 200);
    }

    public function disbursement_withdrawal_method_default(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'is_default' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();
        $method = DisbursementWithdrawalMethod::find($request->id);
        $method->is_default = $request->is_default;
        $method->save();
        DisbursementWithdrawalMethod::whereNot('id', $request->id)->where('delivery_man_id',$dm['id'])->update(['is_default' => 0]);
        return response()->json(['message'=>translate('messages.method_updated_successfully')], 200);
    }

    public function disbursement_withdrawal_method_delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $method = DisbursementWithdrawalMethod::find($request->id);
        $method->delete();
        return response()->json(['message'=>translate('messages.method_deleted_successfully')], 200);
    }

    public function disbursement_report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $limit = $request['limit']??25;
        $offset = $request['offset']??1;

        $dm = DeliveryMan::where(['auth_token' => $request['token']])->first();

        $total_disbursements=DisbursementDetails::where('delivery_man_id',$dm['id'])->latest()->get();
        $paginator=DisbursementDetails::where('delivery_man_id',$dm['id'])->latest()->paginate($limit, ['*'], 'page', $offset);

        $paginator->each(function ($data) {
            $data->withdraw_method?->method_fields ?  $data->withdraw_method->method_fields = json_decode($data->withdraw_method?->method_fields, true) : '';
        });

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'pending' =>(float) $total_disbursements->where('status','pending')->sum('disbursement_amount'),
            'completed' =>(float) $total_disbursements->where('status','completed')->sum('disbursement_amount'),
            'canceled' =>(float) $total_disbursements->where('status','canceled')->sum('disbursement_amount'),
            'complete_day' =>(int) BusinessSetting::where(['key'=>'dm_disbursement_waiting_time'])->first()?->value,
            'disbursements' => $paginator->items()
        ];
        return response()->json($data,200);

    }


    public function wallet_payment_list(Request $request)
    {
        $limit= $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrFail();

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $paginator = AccountTransaction::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        })
        ->where('type', 'collected')
        ->where('created_by' , 'deliveryman')
        ->where('from_id',$dm->id)
        ->where('from_type', 'deliveryman')
        ->latest()

        ->paginate($limit, ['*'], 'page', $offset);

        $temp= [];

        foreach( $paginator->items() as $item)
        {
            $item['amount'] = (float) $item['amount'];
            $item['status'] = 'Approved';
            $item['payment_time'] = \App\CentralLogics\Helpers::time_date_format($item->created_at);

            $temp[] = $item;
        }
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $temp,
        ];

        return response()->json($data, 200);
    }


    public function wallet_provided_earning_list(Request $request)
    {
        $limit= $request['limit'] ?? 25;
        $offset = $request['offset'] ?? 1;
        $dm = DeliveryMan::where(['auth_token' => $request['token']])->firstOrFail();

        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $paginator = ProvideDMEarning::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        })
            ->where('delivery_man_id',$dm->id)
            ->where('method', 'adjustment')
            ->whereIn('ref', ['delivery_man_wallet_adjustment_partial' , 'delivery_man_wallet_adjustment_full' ])
            ->latest()
            ->paginate($limit, ['*'], 'page', $offset);

        $temp= [];

        foreach( $paginator->items() as $item)
        {
            $item['amount'] = (float) $item['amount'];
            $item['status'] = 'Approved';
            $item['payment_time'] = \App\CentralLogics\Helpers::time_date_format($item->created_at);

            $temp[] = $item;
        }
        $data = [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'transactions' => $temp,
        ];

        return response()->json($data, 200);
    }

}
