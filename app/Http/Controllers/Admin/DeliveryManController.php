<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DisbursementHistoryExport;
use App\Models\DisbursementDetails;
use App\Models\Zone;
use App\Models\Message;
use App\Models\DMReview;
use App\Models\UserInfo;
use App\Models\DataSetting;
use App\Models\DeliveryMan;
use App\Models\Conversation;
use App\Models\IncentiveLog;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\OrderTransaction;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DeliveryManListExport;
use Illuminate\Support\Facades\Storage;
use App\Exports\DeliveryManReviewExport;
use App\Exports\DeliveryManEarningExport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Exports\SingleDeliveryManReviewExport;


class DeliveryManController extends Controller
{
    public function index()
    {
        $page_data=   DataSetting::Where('type' , 'deliveryman')->where('key' , 'deliveryman_page_data')->first()?->value;
        $page_data =  $page_data ? json_decode($page_data ,true)  :[];
        return view('admin-views.delivery-man.index',compact('page_data')) ;
    }

    public function list(Request $request)
    {
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', 'all');
        $delivery_men = DeliveryMan::with(['orders','rating','zone'])->
        when(is_numeric($zone_id), function($query) use($zone_id){
            return $query->where('zone_id', $zone_id);
        })->where('type','zone_wise')->latest()->where('application_status','approved')

        ->when(isset($key), function ($q) use($key){
            $q->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('identity_number', 'like', "%{$value}%");
                }
            });
        })
        ->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('admin-views.delivery-man.list', compact('delivery_men', 'zone'));
    }

    // public function search(Request $request){
    //     $key = explode(' ', $request['search']);
    //     $delivery_men=DeliveryMan::
    //     where(function ($q) use ($key) {
    //         foreach ($key as $value) {
    //             $q->orWhere('f_name', 'like', "%{$value}%")
    //                 ->orWhere('l_name', 'like', "%{$value}%")
    //                 ->orWhere('email', 'like', "%{$value}%")
    //                 ->orWhere('phone', 'like', "%{$value}%")
    //                 ->orWhere('identity_number', 'like', "%{$value}%");
    //         }
    //     })
    //     ->where('type','zone_wise')->where('application_status','approved')->get();
    //     return response()->json([
    //         'view'=>view('admin-views.delivery-man.partials._table',compact('delivery_men'))->render(),
    //         'count'=>$delivery_men->count()
    //     ]);
    // }

    public function reviews_list(Request $request){

        $key = explode(' ', $request['search']);
        $reviews=DMReview::with(['delivery_man','customer'])->whereHas('delivery_man',function($query) use ($key){

            $query->where('type','zone_wise')->where(function($q) use($key) {
                    foreach ($key as $value) {
                    $q->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                    }
                });
        })
        ->latest()->paginate(config('default_pagination'));
        return view('admin-views.delivery-man.reviews-list',compact('reviews'));
    }

    public function preview(Request $request, $id, $tab='info')
    {
        $key = explode(' ', $request['search']);
        $dm = DeliveryMan::with(['reviews'])->where('type','zone_wise')->where(['id' => $id])->first();
        if($tab == 'info')
        {
            $reviews=DMReview::where(['delivery_man_id'=>$id])->latest()->paginate(config('default_pagination'));
            return view('admin-views.delivery-man.view.info', compact('dm', 'reviews'));
        }
        else if($tab == 'transaction')
        {
            $date = $request->query('date');
            return view('admin-views.delivery-man.view.transaction', compact('dm', 'date'));
        }
        else if($tab == 'timelog')
        {
            $from = $request->query('from', null);
            $to = $request->query('to', null);
            $timelogs = $dm?->time_logs()->when($from && $to, function($query)use($from, $to){
                $query->whereBetween('date', [$from, $to]);
            })->paginate(config('default_pagination'));
            return view('admin-views.delivery-man.view.timelog', compact('dm', 'timelogs'));
        }
        else if($tab == 'conversation')
        {
            $user = UserInfo::where(['deliveryman_id' => $id])->first();
            if($user){
                $conversations = Conversation::with(['sender', 'receiver', 'last_message'])->WhereUser($user->id)->paginate(8);
            }else{
                $conversations = [];
            }

            return view('admin-views.delivery-man.view.conversations', compact('conversations','dm'));
        } else if ($tab == 'disbursement') {
            $disbursements=DisbursementDetails::where('delivery_man_id', $dm->id)
                ->when(isset($key), function ($q) use ($key){
                    $q->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->orWhere('disbursement_id', 'like', "%{$value}%")
                                ->orWhere('status', 'like', "%{$value}%");
                        }
                    });
                })
                ->latest()->paginate(config('default_pagination'));
            return view('admin-views.delivery-man.view.disbursement', compact('dm','disbursements'));

        }
    }

    public function disbursement_export(Request $request,$id,$type)
    {
        $key = explode(' ', $request['search']);

        $dm= DeliveryMan::find($id);
        $disbursements=DisbursementDetails::where('delivery_man_id', $dm->id)
            ->when(isset($key), function ($q) use ($key){
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('disbursement_id', 'like', "%{$value}%")
                            ->orWhere('status', 'like', "%{$value}%");
                    }
                });
            })
            ->latest()->get();
        $data = [
            'disbursements'=>$disbursements,
            'search'=>$request->search??null,
            'delivery_man'=>$dm->f_name.' '.$dm->l_name,
            'type'=>'dm',
        ];

        if ($request->type == 'excel') {
            return Excel::download(new DisbursementHistoryExport($data), 'Disbursementlist.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new DisbursementHistoryExport($data), 'Disbursementlist.csv');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30',
            'email' => 'required|unique:delivery_men',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|max:20|unique:delivery_men',
            'zone_id' => 'required',
            'earning' => 'required',
            'vehicle_id' => 'required',
            'image' => 'nullable|max:2048',
            'identity_image.*' => 'nullable|max:2048',
            // 'additional_documents' => 'nullable|array|max:5',
            // 'additional_documents.*' => 'nullable|max:2048',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
                function ($attribute, $value, $fail) {
                    if (strpos($value, ' ') !== false) {
                        $fail('The :attribute cannot contain white spaces.');
                    }
                },
            ],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'zone_id.required' => translate('messages.select_a_zone'),
            'vehicle_id.required' => translate('messages.select_a_vehicle'),
            'earning.required' => translate('messages.select_dm_type'),
            // 'additional_documents.max' => translate('You_can_chose_max_5_files_only'),
            'password.required' => translate('The password is required'),
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
            'password.custom' => translate('The password cannot contain white spaces.'),
        ]);

        if ($request->has('image')) {
            $image_name = Helpers::upload(dir:'delivery-man/', format:'png',  image:$request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $id_img_names = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload(dir:'delivery-man/',format: 'png', image:$img);
                array_push($id_img_names, ['img'=>$identity_image, 'storage'=> Helpers::getDisk()]);
            }
            $identity_image = json_encode($id_img_names);
        } else {
            $identity_image = json_encode([]);
        }

        $dm = New DeliveryMan();
        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->phone = $request->phone;
        $dm->identity_number = $request->identity_number;
        $dm->identity_type = $request->identity_type;
        $dm->zone_id = $request->zone_id;
        $dm->vehicle_id = $request->vehicle_id;
        $dm->identity_image = $identity_image;
        $dm->image = $image_name;
        $dm->active = 0;
        $dm->earning = $request->earning;
        $dm->password = bcrypt($request->password);


        if(isset($request->additional_data)  && count($request->additional_data) > 0){
            $dm->additional_data = json_encode($request->additional_data) ;
        }

        $additional_documents = [];
        if ($request->additional_documents) {
            foreach ($request->additional_documents as $key => $data) {
                $additional = [];
                foreach($data as $file){
                    if(is_file($file)){
                        $file_name = Helpers::upload('additional_documents/dm/', $file->getClientOriginalExtension(), $file);
                        $additional[] = ['file'=>$file_name, 'storage'=> Helpers::getDisk()];
                    }
                    $additional_documents[$key] = $additional;
                }
            }
            $dm->additional_documents = json_encode($additional_documents);
        }
        $dm->save();

        Toastr::success(translate('messages.deliveryman_added_successfully'));
        return redirect('admin/delivery-man/list');
    }

    public function edit($id)
    {
        $delivery_man = DeliveryMan::find($id);
        return view('admin-views.delivery-man.edit', compact('delivery_man'));
    }

    public function status(Request $request)
    {
        $delivery_man = DeliveryMan::find($request->id);
        $delivery_man->status = $request->status;

        try {
            if ($request->status == 0) {
                $delivery_man->auth_token = null;
                $deliveryman_push_notification_status=Helpers::getNotificationStatusData('deliveryman','deliveryman_account_block');

                if( $deliveryman_push_notification_status?->push_notification_status  == 'active' && isset($delivery_man->fcm_token))
                {
                    $data = [
                        'title' => translate('messages.suspended'),
                        'description' => translate('messages.your_account_has_been_suspended'),
                        'order_id' => '',
                        'image' => '',
                        'type'=> 'block'
                    ];
                    Helpers::send_push_notif_to_device($delivery_man->fcm_token, $data);

                    DB::table('user_notifications')->insert([
                        'data'=> json_encode($data),
                        'delivery_man_id'=>$delivery_man->id,
                        'created_at'=>now(),
                        'updated_at'=>now()
                    ]);
                }

                $mail_status = Helpers::get_mail_status('suspend_mail_status_dm');
                $notification_status= Helpers::getNotificationStatusData('deliveryman','deliveryman_account_block');

                if ( $notification_status?->mail_status == 'active' &&  config('mail.status') && $mail_status == '1') {
                    Mail::to($delivery_man['email'])->send(new \App\Mail\DmSuspendMail('suspend',$delivery_man['f_name']));
                }
            }else{


                $deliveryman_push_notification_status=Helpers::getNotificationStatusData('deliveryman','deliveryman_account_unblock');
                if( $deliveryman_push_notification_status?->push_notification_status  == 'active' && isset($delivery_man->fcm_token))
                {
                    $data = [
                        'title' => translate('messages.Account_activation'),
                        'description' => translate('messages.your_account_has_been_activated'),
                        'order_id' => '',
                        'image' => '',
                        'type'=> 'unblock'
                    ];
                    Helpers::send_push_notif_to_device($delivery_man->fcm_token, $data);

                    DB::table('user_notifications')->insert([
                        'data'=> json_encode($data),
                        'delivery_man_id'=>$delivery_man->id,
                        'created_at'=>now(),
                        'updated_at'=>now()
                    ]);
                }


                $notification_status=null;
                $notification_status= Helpers::getNotificationStatusData('deliveryman','deliveryman_account_unblock');

                $mail_status = Helpers::get_mail_status('unsuspend_mail_status_dm');
                if ( $notification_status?->mail_status == 'active' &&  config('mail.status') && $mail_status == '1') {
                    Mail::to($delivery_man['email'])->send(new \App\Mail\DmSuspendMail('unsuspend',$delivery_man['f_name']));
                }
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }

        $delivery_man->save();

        Toastr::success(translate('messages.deliveryman_status_updated'));
        return back();
    }

    public function reviews_status(Request $request)
    {
        $review = DMReview::find($request->id);
        $review->status = $request->status;
        $review->save();
        Toastr::success(translate('messages.review_visibility_updated'));
        return back();
    }

    public function earning(Request $request)
    {
        $delivery_man = DeliveryMan::find($request->id);
        $delivery_man->earning = $request->status;

        $delivery_man->save();

        Toastr::success(translate('messages.deliveryman_type_updated'));
        return back();
    }

    public function update_application(Request $request)
    {
        $delivery_man = DeliveryMan::findOrFail($request->id);
        $delivery_man->application_status = $request->status;
        if($request->status == 'approved') $delivery_man->status = 1;
        $delivery_man->save();
        try{
            $notification_status= Helpers::getNotificationStatusData('deliveryman','deliveryman_registration_approval');

            if($request->status== 'approved'){
                if($notification_status?->mail_status == 'active' && config('mail.status') && Helpers::get_mail_status('approve_mail_status_dm') == '1'){
                    Mail::to($delivery_man->email)->send(new \App\Mail\DmSelfRegistration('approved',$delivery_man->f_name.' '.$delivery_man->l_name));
                    }
                    }else{

                        $notification_status=null;
                $notification_status= Helpers::getNotificationStatusData('deliveryman','deliveryman_registration_deny');
                if($notification_status?->mail_status == 'active' && config('mail.status') && Helpers::get_mail_status('deny_mail_status_dm')== '1'){
                    Mail::to($delivery_man->email)->send(new \App\Mail\DmSelfRegistration('denied', $delivery_man->f_name.' '.$delivery_man->l_name));
                }
            }
        }catch(\Exception $ex){
            info($ex->getMessage());
        }
        Toastr::success(translate('messages.application_status_updated_successfully'));
        return to_route('admin.delivery-man.pending');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30',
            'email' => 'required|unique:delivery_men,email,'.$id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|unique:delivery_men,phone,'.$id,
            'vehicle_id' => 'required',
            'earning' => 'required',
            'image' => 'nullable|max:2048',
            'identity_image.*' => 'nullable|max:2048',
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised(),
                function ($attribute, $value, $fail) {
                    if (strpos($value, ' ') !== false) {
                        $fail('The :attribute cannot contain white spaces.');
                    }
                },
            ],

        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'earning.required' => translate('messages.select_dm_type'),
            'vehicle_id.required' => translate('messages.select_a_vehicle'),
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
            'password.custom' => translate('The password cannot contain white spaces.'),

        ]);

        $delivery_man = DeliveryMan::find($id);

        if ($request->has('image')) {
            $image_name = Helpers::update(dir:'delivery-man/',old_image: $delivery_man->image, format:'png', image: $request->file('image'));
        } else {
            $image_name = $delivery_man['image'];
        }

        if ($request->has('identity_image')){
            foreach (json_decode($delivery_man['identity_image'], true) as $img) {
                Helpers::check_and_delete('delivery-man/' , $img);
            }
            $img_keeper = [];
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload(dir:'delivery-man/', format:'png', image:$img);
                array_push($img_keeper, ['img'=>$identity_image, 'storage'=> Helpers::getDisk()]);
            }
            $identity_image = json_encode($img_keeper);
        } else {
            $identity_image = $delivery_man['identity_image'];
        }

        $delivery_man->vehicle_id = $request->vehicle_id;

        $delivery_man->f_name = $request->f_name;
        $delivery_man->l_name = $request->l_name;
        $delivery_man->email = $request->email;
        $delivery_man->phone = $request->phone;
        $delivery_man->identity_number = $request->identity_number;
        $delivery_man->identity_type = $request->identity_type;
        $delivery_man->zone_id = $request->zone_id;
        $delivery_man->identity_image = $identity_image;
        $delivery_man->image = $image_name;
        $delivery_man->earning = $request->earning;
        $delivery_man->password = strlen($request->password)>1?bcrypt($request->password):$delivery_man['password'];
        $delivery_man->save();
        Toastr::success(translate('messages.deliveryman_updated_successfully'));
        return redirect('admin/delivery-man/list');
    }

    public function delete(Request $request)
    {
        $delivery_man = DeliveryMan::find($request->id);
        Helpers::check_and_delete('delivery-man/' , $delivery_man['image']);

        foreach (json_decode($delivery_man['identity_image'], true) as $img) {
            Helpers::check_and_delete('delivery-man/' , $img);
        }
        if($delivery_man->userinfo){

            $delivery_man->userinfo->delete();
        }
        $delivery_man->delete();
        Toastr::success(translate('messages.deliveryman_deleted_successfully'));
        return back();
    }

    public function get_deliverymen(Request $request){
        $key = explode(' ', $request->q);
        $zone_ids = isset($request->zone_ids)?(count($request->zone_ids)>0?$request->zone_ids:[]):0;
        $data=DeliveryMan::when($zone_ids, function($query) use($zone_ids){
            return $query->whereIn('zone_id', $zone_ids);
        })
        ->when($request->earning, function($query){
            return $query->earning();
        })
        ->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('f_name', 'like', "%{$value}%")
                    ->orWhere('l_name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%")
                    ->orWhere('identity_number', 'like', "%{$value}%");
            }
        })->active()->limit(8)->get(['id',DB::raw('CONCAT(f_name, " ", l_name) as text')]);
        return response()->json($data);
    }

    public function get_account_data(DeliveryMan $deliveryman)
    {
        $wallet = $deliveryman->wallet;
        $cash_in_hand = 0;
        $balance = 0;
        $payable_amount = 0;

        if($wallet)
        {
            $cash_in_hand = $wallet->collected_cash;
            $balance = round($wallet->total_earning - $wallet->total_withdrawn - $wallet->pending_withdraw, config('round_up_to_digit'));
            $payable_amount = round($wallet->total_earning - $wallet->total_withdrawn - $wallet->pending_withdraw - $cash_in_hand, config('round_up_to_digit'));
            if($payable_amount < 0){
                $payable_amount = 0;
            }
        }
        return response()->json(['cash_in_hand'=>$cash_in_hand, 'earning_balance'=>$balance , 'payable_amount'=>$payable_amount], 200);

    }

    public function get_conversation_list(Request $request)
    {
        $user = UserInfo::where(['deliveryman_id' => $request->user_id])->first();
        $dm = DeliveryMan::find($request->user_id);
        if($user){
            $conversations = Conversation::with(['sender', 'receiver', 'last_message'])->WhereUser($user->id);
            if($request->query('key')) {
                $key = explode(' ', $request->get('key'));
                $conversations = $conversations->where(function($qu)use($key){
                    $qu->where(function($q)use($key){
                        $q->where('sender_type','!=', 'delivery_man')->whereHas('sender',function($query)use($key){
                            foreach ($key as $value) {
                                $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%");
                            }
                        });
                    })->orWhere(function($q)use($key){
                        $q->where('receiver_type','!=', 'delivery_man')->whereHas('receiver',function($query)use($key){
                            foreach ($key as $value) {
                                $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%")->orWhere('phone', 'like', "%{$value}%");
                            }
                        });
                    });
                });
            }
            $conversations = $conversations->WhereUserType('delivery_man')->paginate(8);
        }else{
            $conversations = [];
        }

        $view = view('admin-views.delivery-man.partials._conversation_list',compact('conversations','dm'))->render();
        return response()->json(['html'=>$view]);

    }

    public function conversation_view($conversation_id,$user_id)
    {
        $convs = Message::where(['conversation_id' => $conversation_id])->get();
        $conversation = Conversation::find($conversation_id);
        $receiver = UserInfo::find($conversation->receiver_id);
        $sender = UserInfo::find($conversation->sender_id);
        $user = UserInfo::find($user_id);
        return response()->json([
            'view' => view('admin-views.delivery-man.partials._conversations', compact('convs', 'user', 'receiver'))->render()
        ]);
    }
    public function dm_list_export(Request $request){
        try{
            $key = explode(' ', $request['search']);
            $zone_id = $request->query('zone_id', 'all');
            $delivery_men = DeliveryMan::with(['orders','rating','zone'])
            ->when(is_numeric($zone_id), function($query) use($zone_id){
                return $query->where('zone_id', $zone_id);
            })
            ->where('type','zone_wise')->latest()->where('application_status','approved')
            ->when(isset($key), function ($q) use($key){
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%")
                            ->orWhere('l_name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%")
                            ->orWhere('phone', 'like', "%{$value}%")
                            ->orWhere('identity_number', 'like', "%{$value}%");
                    }
                });
            })
            ->orderBy('id','desc')->get();

            $data = [
                'delivery_men'=>$delivery_men,
                'search'=>$request->search??null,
                'zone'=>is_numeric($zone_id)?Helpers::get_zones_name($zone_id):null,
            ];

            if ($request->type == 'excel') {
                return Excel::download(new DeliveryManListExport($data), 'DeliveryMans.xlsx');
            } else if ($request->type == 'csv') {
                return Excel::download(new DeliveryManListExport($data), 'DeliveryMans.csv');
            }

        }  catch(\Exception $e){
            Toastr::error("line___{$e->getLine()}",$e->getMessage());
            info(["line___{$e->getLine()}",$e->getMessage()]);
            return back();
        }
    }


    public function pending(Request $request)
    {
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', 'all');
        $vehicle_id = $request->query('vehicle_id', 'all');
        $job_type = $request->query('job_type', 'all');

        $delivery_men = DeliveryMan::when(is_numeric($zone_id), function($query) use($zone_id){
            return $query->where('zone_id', $zone_id);
        })
        ->when(isset($key),function($query)use($key){
            $query->where(function($q)use($key){
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                ->orWhere('l_name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")
                ->orWhere('phone', 'like', "%{$value}%")
                ->orWhere('identity_number', 'like', "%{$value}%");
                }
            });
        })
        ->when(is_numeric($vehicle_id) , function($q)use($vehicle_id){
            $q->where('vehicle_id', $vehicle_id);
        })
        ->when($job_type == 'salary_base', function($q){
            $q->where('earning', 0);
        })
        ->when($job_type == 'freelance', function($q){
            $q->where('earning', 1);
        })

        ->with('zone')->where('type','zone_wise')->where('application_status', 'pending')->latest()->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('admin-views.delivery-man.pending_list', compact('delivery_men', 'zone'));


    }
    public function denied(Request $request)
    {
        $key = explode(' ', $request['search']);
        $zone_id = $request->query('zone_id', 'all');

        $vehicle_id = $request->query('vehicle_id', 'all');
        $job_type = $request->query('job_type', 'all');

        $delivery_men = DeliveryMan::when(is_numeric($zone_id), function($query) use($zone_id){
            return $query->where('zone_id', $zone_id);
        })
        ->when(isset($key),function($query)use($key){
            $query->where(function($q)use($key){
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                ->orWhere('l_name', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%")
                ->orWhere('phone', 'like', "%{$value}%")
                ->orWhere('identity_number', 'like', "%{$value}%");
                }
            });
        })
        ->when(is_numeric($vehicle_id) , function($q)use($vehicle_id){
            $q->where('vehicle_id', $vehicle_id);
        })
        ->when($job_type == 'salary_base', function($q){
            $q->where('earning', 0);
        })
        ->when($job_type == 'freelance', function($q){
            $q->where('earning', 1);
        })
        ->with('zone')->where('type','zone_wise')->where('application_status', 'denied')->latest()->paginate(config('default_pagination'));
        $zone = is_numeric($zone_id)?Zone::findOrFail($zone_id):null;
        return view('admin-views.delivery-man.denied', compact('delivery_men', 'zone'));
    }

    public function get_incentives(Request $request)
    {
        $incentives = IncentiveLog::when($request->search, function ($query) use ($request) {
            $key = explode(' ', $request->search);
            $query->whereHas('deliveryman', function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%");
                        $q->orWhere('l_name', 'like', "%{$value}%");
                        $q->orWhere('phone', 'like', "%{$value}%");
                        $q->orWhere('email', 'like', "%{$value}%");
                    }
                });
            });
        })
            ->where('status', '!=', 'pending')
            ->latest()->paginate(config('default_pagination'));
        return view('admin-views.delivery-man.incentive', compact('incentives'));
    }
    public function pending_incentives(Request $request)
    {
        $incentives = IncentiveLog::
        when($request->search, function ($query) use ($request) {
            $key = explode(' ', $request->search);
            $query->whereHas('deliveryman', function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('f_name', 'like', "%{$value}%");
                        $q->orWhere('l_name', 'like', "%{$value}%");
                        $q->orWhere('phone', 'like', "%{$value}%");
                        $q->orWhere('email', 'like', "%{$value}%");
                    }
                });
            });
        })
            ->whereStatus('pending')
            ->latest()->paginate(config('default_pagination'));
        return view('admin-views.delivery-man.incentive', compact('incentives'));
    }

    public function update_incentive_status(Request $request)
    {
        $request->validate([
            'status' => 'required|in:denied',
            'id' => 'required'
        ]);

        $incentive = IncentiveLog::findOrFail($request->id);

        if ($incentive->status == "pending") {
            $incentive->status = $request->status;
            $incentive->save();
            Toastr::success(translate('messages.incentive_denied'));
            return back();
        }

    }

    public function update_all_incentive_status(Request $request)
    {
        $request->validate([
            'incentive_id' => 'required'
        ]);
        $incentives = IncentiveLog::whereIn('id', $request->incentive_id)->get();
        foreach ($incentives as $incentive) {
            Helpers::dm_wallet_transaction(delivery_man_id:$incentive->delivery_man_id, amount:$incentive->incentive,referance: null, type:'incentive');
            $incentive->status = "approved";
            $incentive->save();
        }
        Toastr::success(translate('messages.succesfully_approved_incentive'));
        return back();
    }

    public function get_bonus(Request $request)
    {
        $data = WalletTransaction::where('transaction_type', 'dm_admin_bonus')
        ->when($request->search, function ($query) use ($request) {
                $query->where(function($query) use ($request) {
                    $key = explode(' ', $request->search);
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->Where('transaction_id', 'like', "%{$value}%");
                        }
                    })
                    ->orWhereHas('delivery_man', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->orWhere('f_name', 'like', "%{$value}%");
                                $q->orWhere('l_name', 'like', "%{$value}%");
                                $q->orWhere('phone', 'like', "%{$value}%");
                                $q->orWhere('email', 'like', "%{$value}%");
                            }
                        });
                    });
                });
        })

        ->paginate(config('default_pagination'));
        return view('admin-views.delivery-man.bonus', compact('data'));
    }

    public function add_bonus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_man_id'=>'exists:delivery_men,id',
            'amount'=>'numeric|min:.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        if(Helpers::dm_wallet_transaction(delivery_man_id:$request->delivery_man_id, amount:$request->amount, referance: $request->referance)){
            return response()->json(['message'=>trans('messages.bonus_added_successfully')], 200);
        }
        return response()->json(['errors' => [['code'=>'transaction-failed', 'message'=>translate('messages.faield_to_create_transaction')]]]);
    }

    public function earning_export(Request $request){
        try{
            $date = $request->date;
            $dm = DeliveryMan::with(['reviews'])->where('type','zone_wise')->where(['id' => $request->id])->first();
            $earnings=OrderTransaction::where('delivery_man_id', $request->id)
            ->when($date, function($query)use($date){
                return $query->whereDate('created_at', $date);
            })
            ->get();

            $data = [
                'dm'=>$dm,
                'earnings'=>$earnings,
                'date'=>$request->date??null,
            ];

            if ($request->type == 'excel') {
                return Excel::download(new DeliveryManEarningExport($data), 'DeliveryManEarnings.xlsx');
            } else if ($request->type == 'csv') {
                return Excel::download(new DeliveryManEarningExport($data), 'DeliveryManEarnings.csv');
            }
        }  catch(\Exception $e){
            Toastr::error("line___{$e->getLine()}",$e->getMessage());
            info(["line___{$e->getLine()}",$e->getMessage()]);
            return back();
        }
    }

    public function reviews_export(Request $request){
        try{
                $key = explode(' ', $request['search']);
            $reviews=DMReview::with(['delivery_man','customer'])->whereHas('delivery_man',function($query) use ($key){
                $query->where('type','zone_wise')->where(function($q) use($key) {
                        foreach ($key as $value) {
                        $q->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                        }
                    });
            })
            ->latest()
            ->get();

            $data = [
                'reviews'=>$reviews,
                'search'=>$request->search??null,
            ];

            if ($request->type == 'excel') {
                return Excel::download(new DeliveryManReviewExport($data), 'DeliveryManReviews.xlsx');
            } else if ($request->type == 'csv') {
                return Excel::download(new DeliveryManReviewExport($data), 'DeliveryManReviews.csv');
            }
        }  catch(\Exception $e){
            Toastr::error("line___{$e->getLine()}",$e->getMessage());
            info(["line___{$e->getLine()}",$e->getMessage()]);
            return back();
        }
    }

    public function review_export(Request $request){
        try{
            $dm = DeliveryMan::with(['reviews'])->where('type','zone_wise')->where(['id' => $request->id])->first();
            $reviews=DMReview::where(['delivery_man_id'=>$request->id])->latest()->get();

            $data = [
                'dm'=>$dm,
                'reviews'=>$reviews,
                'search'=>$request->search??null,
            ];

            if ($request->type == 'excel') {
                return Excel::download(new SingleDeliveryManReviewExport($data), 'DeliveryManReviews.xlsx');
            } else if ($request->type == 'csv') {
                return Excel::download(new SingleDeliveryManReviewExport($data), 'DeliveryManReviews.csv');
            }
        }  catch(\Exception $e){
            Toastr::error("line___{$e->getLine()}",$e->getMessage());
            info(["line___{$e->getLine()}",$e->getMessage()]);
            return back();
        }
    }

    public function pending_dm_view($id){
        $dm = DeliveryMan::with(['reviews'])->where('type','zone_wise')->where(['id' => $id])->first();

        return view('admin-views.delivery-man.pending_list_view', compact('dm'));
    }

}
