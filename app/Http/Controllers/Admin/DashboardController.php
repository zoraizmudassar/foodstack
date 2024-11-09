<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Food;
use App\Models\User;
use App\Models\Order;
use App\Models\Wishlist;
use App\Scopes\ZoneScope;
use App\Models\Restaurant;
use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use App\Models\OrderTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $params = [
            'zone_id' => $request['zone_id'] ?? 'all',
            'statistics_type' => $request['statistics_type'] ?? 'overall',
            'user_overview' => $request['user_overview'] ?? 'overall',
            'business_overview' => $request['business_overview'] ?? 'overall',
        ];
        session()->put('dash_params', $params);
        $data = self::dashboard_data();
        $total_sell = $data['total_sell'];
        $total_subs = $data['total_subs'];
        $commission = $data['commission'];
        return view('admin-views.dashboard', compact('data', 'total_sell','total_subs' ,'commission', 'params'));
    }

    public function order(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'statistics_type') {
                $params['statistics_type'] = $request['statistics_type'];
            }
        }
        session()->put('dash_params', $params);

        if ($params['zone_id'] != 'all') {
            $restaurant_ids = Restaurant::where(['zone_id' => $params['zone_id']])->pluck('id')->toArray();
        } else {
            $restaurant_ids = Restaurant::pluck('id')->toArray();
        }
        $data = self::order_stats_calc($params['zone_id']);
        return response()->json([
            'view' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render(),
            'order_stats_top' => view('admin-views.partials._order-statics', compact('data'))->render()
        ], 200);
    }

    public function zone(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'zone_id') {
                $params['zone_id'] = $request['zone_id'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::dashboard_data();
        $total_subs = $data['total_subs'];
        $total_sell = $data['total_sell'];
        $commission = $data['commission'];
        $popular = $data['popular'];
        $top_deliveryman = $data['top_deliveryman'];
        $top_rated_foods = $data['top_rated_foods'];
        $top_restaurants = $data['top_restaurants'];
        $top_sell = $data['top_sell'];

        return response()->json([
            'popular_restaurants' => view('admin-views.partials._popular-restaurants', compact('popular'))->render(),
            'top_deliveryman' => view('admin-views.partials._top-deliveryman', compact('top_deliveryman'))->render(),
            'top_rated_foods' => view('admin-views.partials._top-rated-foods', compact('top_rated_foods'))->render(),
            'top_restaurants' => view('admin-views.partials._top-restaurants', compact('top_restaurants'))->render(),
            'top_selling_foods' => view('admin-views.partials._top-selling-foods', compact('top_sell'))->render(),

            'order_stats' => view('admin-views.partials._dashboard-order-stats', compact('data'))->render(),
            'stat_zone' => view('admin-views.partials._zone-change', compact('data'))->render(),
            'order_stats_top' => view('admin-views.partials._order-statics', compact('data'))->render(),
            'user_overview' => view('admin-views.partials._user-overview-chart', compact('data'))->render(),
            'monthly_graph' => view('admin-views.partials._monthly-earning-graph', compact('total_sell','total_subs', 'commission'))->render(),
        ], 200);
    }

    public function user_overview(Request $request)
    {
        $params = session('dash_params');
        foreach ($params as $key => $value) {
            if ($key == 'user_overview') {
                $params['user_overview'] = $request['user_overview'];
            }
        }
        session()->put('dash_params', $params);

        $data = self::user_overview_calc($params['zone_id']);

        return response()->json([
            'view' => view('admin-views.partials._user-overview-chart', compact('data'))->render()
        ], 200);
    }

    public function order_stats_calc($zone_id)
    {
        $params = session('dash_params');


        if ($params['statistics_type'] == 'today') {
            $searching_for_dm = Order::SearchingForDeliveryman()->hasSubscriptionToday()->whereDate('created_at', Carbon::now());
            $accepted_by_dm = Order::AccepteByDeliveryman()->hasSubscriptionToday()->whereDate('accepted', Carbon::now());
            $preparing_in_rs = Order::Preparing()->whereDate('processing', Carbon::now());
            $picked_up = Order::FoodOnTheWay()->whereDate('picked_up', Carbon::now());
            $delivered = Order::Notpos()->HasSubscriptionToday()->Delivered()->whereDate('delivered', Carbon::now());
            $canceled = Order::Notpos()->HasSubscriptionToday()->where(['order_status' => 'canceled'])->whereDate('canceled', Carbon::now());
            $refund_requested = Order::where(['order_status' => 'refund_requested'])->whereDate('refund_requested', Carbon::now());
            $refunded = Order::where(['order_status' => 'refunded'])->whereDate('refunded', Carbon::now());
        }
        elseif ($params['statistics_type'] == 'this_month') {

            $searching_for_dm = Order::SearchingForDeliveryman()->hasSubscriptionToday()->whereMonth('created_at', Carbon::now());
            $accepted_by_dm = Order::AccepteByDeliveryman()->hasSubscriptionToday()->whereMonth('accepted', Carbon::now());
            $preparing_in_rs = Order::Preparing()->whereMonth('processing', Carbon::now());
            $picked_up = Order::FoodOnTheWay()->whereMonth('picked_up', Carbon::now());
            $delivered = Order::Notpos()->HasSubscriptionToday()->Delivered()->whereMonth('delivered', Carbon::now());
            $canceled = Order::Notpos()->HasSubscriptionToday()->where(['order_status' => 'canceled'])->whereMonth('canceled', Carbon::now());
            $refund_requested = Order::where(['order_status' => 'refund_requested'])->whereMonth('refund_requested', Carbon::now());
            $refunded = Order::where(['order_status' => 'refunded'])->whereMonth('refunded', Carbon::now());
        }
        elseif ($params['statistics_type'] == 'this_year') {

            $searching_for_dm = Order::SearchingForDeliveryman()->hasSubscriptionToday()->whereYear('created_at', Carbon::now());
            $accepted_by_dm = Order::AccepteByDeliveryman()->hasSubscriptionToday()->whereYear('accepted', Carbon::now());
            $preparing_in_rs = Order::Preparing()->whereYear('processing', Carbon::now());
            $picked_up = Order::FoodOnTheWay()->whereYear('picked_up', Carbon::now());
            $delivered = Order::Notpos()->HasSubscriptionToday()->Delivered()->whereYear('delivered', Carbon::now());
            $canceled = Order::Notpos()->HasSubscriptionToday()->where(['order_status' => 'canceled'])->whereYear('canceled', Carbon::now());
            $refund_requested = Order::where(['order_status' => 'refund_requested'])->whereYear('refund_requested', Carbon::now());
            $refunded = Order::where(['order_status' => 'refunded'])->whereYear('refunded', Carbon::now());
        }
        elseif ($params['statistics_type'] == 'this_week') {

            $searching_for_dm = Order::SearchingForDeliveryman()->hasSubscriptionToday()->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $accepted_by_dm = Order::AccepteByDeliveryman()->hasSubscriptionToday()->whereBetween('accepted', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $preparing_in_rs = Order::Preparing()->whereBetween('processing', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $picked_up = Order::FoodOnTheWay()->whereBetween('picked_up', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $delivered = Order::Notpos()->HasSubscriptionToday()->Delivered()->whereBetween('delivered', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $canceled = Order::Notpos()->HasSubscriptionToday()->where(['order_status' => 'canceled'])->whereBetween('canceled', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $refund_requested = Order::where(['order_status' => 'refund_requested'])->whereBetween('refund_requested', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            $refunded = Order::where(['order_status' => 'refunded'])->whereBetween('refunded', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
        }
        else {
            $searching_for_dm = Order::SearchingForDeliveryman()->hasSubscriptionToday();
            $accepted_by_dm = Order::AccepteByDeliveryman()->hasSubscriptionToday();
            $preparing_in_rs = Order::Preparing();
            $picked_up = Order::FoodOnTheWay();
            $delivered = Order::Notpos()->HasSubscriptionToday()->Delivered();
            $canceled = Order::Notpos()->HasSubscriptionToday()->Canceled();
            $refund_requested = Order::failed();
            $refunded = Order::Refunded();
        }

        if(is_numeric($zone_id))
        {
            $searching_for_dm = $searching_for_dm->Notpos()->OrderScheduledIn(30)->hasSubscriptionToday()->where('zone_id', $zone_id)->count();
            $accepted_by_dm = $accepted_by_dm->hasSubscriptionToday()->Notpos()->where('zone_id', $zone_id)->count();
            $preparing_in_rs = $preparing_in_rs->Notpos()->where('zone_id', $zone_id)->count();
            $picked_up = $picked_up->Notpos()->where('zone_id', $zone_id)->count();
            $delivered = $delivered->Notpos()->where('zone_id', $zone_id)->count();
            $canceled = $canceled->Notpos()->where('zone_id', $zone_id)->count();
            $refund_requested = $refund_requested->Notpos()->where('zone_id', $zone_id)->count();
            $refunded = $refunded->Notpos()->where('zone_id', $zone_id)->count();
        }
        else
        {
            $searching_for_dm = $searching_for_dm->Notpos()->hasSubscriptionToday()->OrderScheduledIn(30)->count();
            $accepted_by_dm = $accepted_by_dm->Notpos()->count();
            $preparing_in_rs = $preparing_in_rs->Notpos()->count();
            $picked_up = $picked_up->Notpos()->count();
            $delivered = $delivered->Notpos()->count();
            $canceled = $canceled->Notpos()->count();
            $refund_requested = $refund_requested->Notpos()->count();
            $refunded = $refunded->Notpos()->count();
        }


        $data = [
            'searching_for_dm' => $searching_for_dm,
            'accepted_by_dm' => $accepted_by_dm,
            'preparing_in_rs' => $preparing_in_rs,
            'picked_up' => $picked_up,
            'delivered' => $delivered,
            'canceled' => $canceled,
            'refund_requested' => $refund_requested,
            'refunded' => $refunded
        ];


        return $data;


    }

    public function user_overview_calc($zone_id)
    {
        $params = session('dash_params');
        if(is_numeric($zone_id))
        {
            $customer = User::where('zone_id', $zone_id);
            $restaurants = Restaurant::where(['zone_id' => $zone_id])->wherehas('vendor', function($query) {
                return  $query->where('status' , 1);
            });
            $delivery_man = DeliveryMan::where('zone_id', $zone_id)->where('application_status' ,'approved')->Zonewise();
        }
        else
        {
            $customer = User::whereNotNull('id');
            $restaurants = Restaurant::whereNotNull('id')->wherehas('vendor', function($query) {
                return  $query->where('status' , 1);
            });
            $delivery_man = DeliveryMan::Zonewise()->where('application_status' ,'approved' );
        }
        //user overview
        if ($params['user_overview'] == 'overall') {
            $customer = $customer->count();
            $restaurants = $restaurants->count();
            $delivery_man = $delivery_man->count();
        }
        elseif ($params['user_overview'] == 'this_year') {
            $customer = $customer->whereYear('created_at', Carbon::now())->count();
            $restaurants = $restaurants->whereYear('created_at', Carbon::now())->count();
            $delivery_man = $delivery_man->whereYear('created_at', Carbon::now())->count();
        }
        elseif ($params['user_overview'] == 'today') {
            $customer = $customer->whereDate('created_at', Carbon::now())->count();
            $restaurants = $restaurants->whereDate('created_at', Carbon::now())->count();
            $delivery_man = $delivery_man->whereDate('created_at', Carbon::now())->count();
        }
        elseif ($params['user_overview'] == 'this_week') {
            $customer = $customer->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $restaurants = $restaurants->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
            $delivery_man = $delivery_man->whereBetween('created_at', [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')])->count();
        }

        else {
            $customer = $customer->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
            $restaurants = $restaurants->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
            $delivery_man = $delivery_man->whereMonth('created_at', date('m'))
                ->whereYear('created_at', date('Y'))->count();
        }
        $data = [
            'customer' => $customer,
            'restaurants' => $restaurants,
            'delivery_man' => $delivery_man
        ];
        return $data;
    }


    public function dashboard_data()
    {
        $params = session('dash_params');
        $data_os = self::order_stats_calc($params['zone_id']);
        $data_uo = self::user_overview_calc($params['zone_id']);

        $popular = Wishlist::with(['restaurant'])
        ->whereHas('restaurant')
        ->when(is_numeric($params['zone_id']), function($q)use($params){
            return $q->whereHas('restaurant', function($query)use($params){
                return $query->where('zone_id', $params['zone_id']);
            });
        })
        ->select('restaurant_id', DB::raw('COUNT(restaurant_id) as count'))->groupBy('restaurant_id')->orderBy('count', 'DESC')->limit(12)->get();
        $top_sell = Food::withoutGlobalScopes([ZoneScope::class])
            ->when(is_numeric($params['zone_id']),function($q)use($params){
                return $q->whereHas('restaurant', function($query)use($params){
                    return $query->where('zone_id', $params['zone_id']);
                });
            })
            ->orderBy("order_count", 'desc')
            ->take(6)
            ->get();
        $top_rated_foods = Food::withoutGlobalScopes([ZoneScope::class])
            ->when(is_numeric($params['zone_id']),function($q)use($params){
                return $q->whereHas('restaurant', function($query)use($params){
                    return $query->where('zone_id', $params['zone_id']);
                });
            })
            ->where('avg_rating', '>', 0)
            ->orderBy('avg_rating','desc')
            ->orderBy('rating_count','desc')
            ->limit(6)
            ->get();

        $top_deliveryman = DeliveryMan::
            when(is_numeric($params['zone_id']), function($q)use($params){
                return $q->where('zone_id', $params['zone_id']);
            })
            ->where('type','zone_wise')
            ->orderBy("order_count", 'desc')
            ->take(6)
            ->get();

        $top_restaurants = Restaurant::
            when(is_numeric($params['zone_id']), function($q)use($params){
                return $q->where('zone_id', $params['zone_id']);
            })
            ->orderBy("order_count", 'desc')
            ->take(6)
            ->get();

        $total_sell = [];
        $commission = [];
        $total_subs= [];
        for ($i = 1; $i <= 12; $i++) {
            $total_sell[$i] = OrderTransaction::NotRefunded()
                ->when(is_numeric($params['zone_id']), function($q)use($params){
                    return $q->where('zone_id', $params['zone_id']);
                })
                ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                ->sum('order_amount');


                $total_subs[$i] = SubscriptionTransaction::
                // ->when(is_numeric($params['zone_id']), function($q)use($params){
                //     return $q->where('zone_id', $params['zone_id']);
                // })
                whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                ->sum('paid_amount');



            $commission[$i] = OrderTransaction::NotRefunded()
                ->when(is_numeric($params['zone_id']), function($q)use($params){
                    return $q->where('zone_id', $params['zone_id']);
                })
                ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                // ->sum('admin_commission');
                ->sum(DB::raw('admin_commission + admin_expense - delivery_fee_comission'));

            $commission[$i] += OrderTransaction::NotRefunded()
                ->when(is_numeric($params['zone_id']), function($q)use($params){
                    return $q->where('zone_id', $params['zone_id']);
                })
                ->whereMonth('created_at', $i)->whereYear('created_at', now()->format('Y'))
                ->sum('delivery_fee_comission');
        }

        $dash_data = array_merge($data_os, $data_uo);
        $dash_data['popular'] = $popular;
        $dash_data['top_sell'] = $top_sell;
        $dash_data['top_rated_foods'] = $top_rated_foods;
        $dash_data['top_deliveryman'] = $top_deliveryman;
        $dash_data['top_restaurants'] = $top_restaurants;
        $dash_data['total_sell'] = $total_sell;
        $dash_data['total_subs'] = $total_subs;
        $dash_data['commission'] = $commission;

        return $dash_data;
    }
}
