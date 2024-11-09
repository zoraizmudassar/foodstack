<?php

namespace App\Http\Controllers\Vendor;

use App\Models\DMReview;
use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class DeliveryManController extends Controller
{
    public function index()
    {
        return view('vendor-views.delivery-man.index');
    }

    public function list(Request $request)
    {
        $key = explode(' ', $request['search']);
        $delivery_men = DeliveryMan::where('restaurant_id', Helpers::get_restaurant_id())
        ->when(isset($key) , function($query) use($key){
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('identity_number', 'like', "%{$value}%");
                }
            });
        })

        ->latest()->paginate(config('default_pagination'));
        return view('vendor-views.delivery-man.list', compact('delivery_men'));
    }


    public function reviews_list(){
        $reviews=DMReview::with(['delivery_man','customer'])->latest()->paginate(config('default_pagination'));
        return view('vendor-views.delivery-man.reviews-list',compact('reviews'));
    }

    public function preview($id, $tab='info')
    {
        $dm = DeliveryMan::with(['reviews'])->where('restaurant_id', Helpers::get_restaurant_id())->where(['id' => $id])->first();
        if($tab == 'info')
        {
            $reviews=DMReview::where(['delivery_man_id'=>$id])->latest()->paginate(config('default_pagination'));
            return view('vendor-views.delivery-man.view.info', compact('dm', 'reviews'));
        }
        else if($tab == 'transaction')
        {
            return view('vendor-views.delivery-man.view.transaction', compact('dm'));
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30',
            'email' => 'required|unique:delivery_men',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|unique:delivery_men',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],

            'image' => 'nullable|max:2048',
            'identity_image.*' => 'nullable|max:2048',
        ],[
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
            'password.custom' => translate('The password cannot contain white spaces.'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        if ($request->has('image')) {
            $image_name = Helpers::upload(dir:'delivery-man/', format:'png',image: $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $id_img_names = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload(dir:'delivery-man/',format: 'png',image: $img);
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
        $dm->restaurant_id =  Helpers::get_restaurant_id();
        $dm->identity_image = $identity_image;
        $dm->image = $image_name;
        $dm->active = 0;
        $dm->earning = 0;
        $dm->type = 'restaurant_wise';
        $dm->password = bcrypt($request->password);
        $dm->save();

        return response()->json(['message' => translate('messages.deliveryman_added_successfully')], 200);

        return redirect('restaurant-panel/delivery-man/list');
    }

    public function edit($id)
    {
        $delivery_man = DeliveryMan::find($id);
        return view('vendor-views.delivery-man.edit', compact('delivery_man'));
    }

    public function status(Request $request)
    {
        $delivery_man = DeliveryMan::find($request->id);
        $delivery_man->status = $request->status;

        try
        {
            if($request->status == 0)
            {   $delivery_man->auth_token = null;


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

            } else{

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
            }

        }
        catch (\Exception $e) {
            info($e->getMessage());
            Toastr::warning(translate('messages.push_notification_faild'));
        }

        $delivery_man->save();

        Toastr::success(translate('messages.deliveryman_status_updated'));
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

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30',
            'email' => 'required|unique:delivery_men,email,'.$id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9|unique:delivery_men,phone,'.$id,
            'password' => ['nullable', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],

            'image' => 'nullable|max:2048',
            'identity_image.*' => 'nullable|max:2048',
        ],[
            'password.min_length' => translate('The password must be at least :min characters long'),
            'password.mixed' => translate('The password must contain both uppercase and lowercase letters'),
            'password.letters' => translate('The password must contain letters'),
            'password.numbers' => translate('The password must contain numbers'),
            'password.symbols' => translate('The password must contain symbols'),
            'password.uncompromised' => translate('The password is compromised. Please choose a different one'),
            'password.custom' => translate('The password cannot contain white spaces.'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $delivery_man = DeliveryMan::find($id);

        if ($request->has('image')) {
            $image_name = Helpers::update(dir:'delivery-man/',old_image: $delivery_man->image, format:'png',image: $request->file('image'));
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

        $delivery_man->f_name = $request->f_name;
        $delivery_man->l_name = $request->l_name;
        $delivery_man->email = $request->email;
        $delivery_man->phone = $request->phone;
        $delivery_man->identity_number = $request->identity_number;
        $delivery_man->identity_type = $request->identity_type;
        $delivery_man->identity_image = $identity_image;
        $delivery_man->image = $image_name;

        $delivery_man->password = strlen($request->password)>1?bcrypt($request->password):$delivery_man['password'];
        $delivery_man->save();

        return response()->json(['message' => translate('messages.deliveryman_updated_successfully')], 200);

        return redirect('restaurant-panel/delivery-man/list');
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
        })->where('restaurant_id', Helpers::get_restaurant_id())->limit(8)->get(['id',DB::raw('CONCAT(f_name, " ", l_name) as text')]);
        return response()->json($data);
    }

    public function get_account_data(DeliveryMan $deliveryman)
    {
        $wallet = $deliveryman?->wallet;
        $cash_in_hand = 0;
        $balance = 0;

        if($wallet)
        {
            $cash_in_hand = $wallet->collected_cash;
            $balance = $wallet->total_earning - $wallet->total_withdrawn - $wallet->pending_withdraw;
        }
        return response()->json(['cash_in_hand'=>$cash_in_hand, 'earning_balance'=>$balance], 200);

    }
}
