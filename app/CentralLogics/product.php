<?php

namespace App\CentralLogics;

use App\Models\Food;
use App\Models\ItemCampaign;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductLogic
{
    public static function get_product($id , $campaign=false)
    {
        if($campaign == true){
            return ItemCampaign::find($id);
        }
        return Food::active()->when(is_numeric($id),function ($qurey) use($id){
                        $qurey-> where('id', $id);
                    })
                    ->when(!is_numeric($id),function ($qurey) use($id){
                        $qurey-> where('slug', $id);
                    })
                    ->first();
    }

    public static function get_latest_products($limit, $offset, $restaurant_id, $category_id, $type='all')
    {
        $paginator = Food::active()->type($type);
        if($category_id != 0)
        {
            $paginator = $paginator->whereHas('category',function($q)use($category_id){
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        }
        $paginator = $paginator->where('restaurant_id', $restaurant_id)->latest()->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
    }

    public static function get_related_products($product_id)
    {
        $product = Food::find($product_id);
        return Food::active()
        ->whereHas('restaurant', function($query){
            $query->Weekday();
        })
        ->where('category_ids', $product->category_ids)
        ->where('id', '!=', $product->id)
        ->limit(10)
        ->get();
    }

    public static function search_products($name, $zone_id, $limit = 10, $offset = 1)
    {
        $key = explode(' ', $name);
        $paginator = Food::active()->whereHas('restaurant', function($q)use($zone_id){
            $q->whereIn('zone_id', $zone_id)->Weekday();
        })->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
    }

    public static function popular_products($zone_id, $limit = null, $offset = null, $type='all',$longitude=0,$latitude=0)
    {
        $popular_food_default_status = \App\Models\BusinessSetting::where('key', 'popular_food_default_status')->first();
        $popular_food_default_status = $popular_food_default_status ? $popular_food_default_status->value : 1;
        $popular_food_sort_by_general = \App\Models\PriorityList::where('name', 'popular_food_sort_by_general')->where('type','general')->first();
        $popular_food_sort_by_general = $popular_food_sort_by_general ? $popular_food_sort_by_general->value : '';
        $popular_food_sort_by_unavailable = \App\Models\PriorityList::where('name', 'popular_food_sort_by_unavailable')->where('type','unavailable')->first();
        $popular_food_sort_by_unavailable = $popular_food_sort_by_unavailable ? $popular_food_sort_by_unavailable->value : '';
        $popular_food_sort_by_temp_closed = \App\Models\PriorityList::where('name', 'popular_food_sort_by_temp_closed')->where('type','temp_closed')->first();
        $popular_food_sort_by_temp_closed = $popular_food_sort_by_temp_closed ? $popular_food_sort_by_temp_closed->value : '';


        if($limit != null && $offset != null)
        {
            if ($popular_food_default_status == '1'){
                $paginator = Food::whereHas('restaurant', function($q)use($zone_id){
                    $q->whereIn('zone_id', $zone_id)->Weekday();
                })->active()->type($type)->has('reviews')->popular()->paginate($limit, ['*'], 'page', $offset);
            }

            if ($popular_food_default_status == '0'){
                $time = Carbon::now()->toTimeString();

                $query = Food::Active()->with('restaurant')
                    ->select(['food.*'])
                    ->type($type)
                    // ->leftJoin('restaurants', 'food.restaurant_id', '=', 'restaurants.id')
                    ->whereHas('restaurant', function($q) use ($zone_id) {
                        $q->whereIn('zone_id', $zone_id)->Weekday();
                    })
                    ->selectSub(function ($subQuery) {
                        $subQuery->selectRaw('active as temp_available')
                            ->from('restaurants')
                            ->whereColumn('restaurants.id', 'food.restaurant_id');
                    }, 'temp_available')
                    ->selectSub(function ($subQuery) {
                        $subQuery->selectRaw('IF(((select count(*) from `restaurant_schedule` where `restaurants`.`id` = `restaurant_schedule`.`restaurant_id` and `restaurant_schedule`.`day` = ? and `restaurant_schedule`.`opening_time` < ? and `restaurant_schedule`.`closing_time` > ?) > 0), true, false) as open', [now()->dayOfWeek, now()->format('H:i:s'), now()->format('H:i:s')])
                            ->from('restaurants')
                            ->whereColumn('restaurants.id', 'food.restaurant_id');
                    }, 'open');

                if($popular_food_sort_by_unavailable == 'remove'){
                    $query = $query->available($time);
                }elseif($popular_food_sort_by_unavailable == 'last'){
                    $query = $query->orderBy(DB::raw("CASE WHEN available_time_starts <= '$time' AND available_time_ends >= '$time' THEN 0 ELSE 1 END"));
                }

                if($popular_food_sort_by_temp_closed == 'remove'){
                    $query = $query->having('temp_available', '>', 0);
                }elseif($popular_food_sort_by_temp_closed == 'last'){
                    $query = $query->orderByDesc('temp_available');
                }

                if ($popular_food_sort_by_general == 'nearest_first') {
                    $query = $query->selectSub(function ($subQuery) use ($longitude, $latitude) {
                        $subQuery->selectRaw('ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance', [$longitude, $latitude])
                            ->from('restaurants')
                            ->whereColumn('restaurants.id', 'food.restaurant_id');
                    }, 'distance')
                        ->orderBy('distance');
                } elseif ($popular_food_sort_by_general == 'rating') {
                    $query = $query->orderByDesc('avg_rating');
                } elseif ($popular_food_sort_by_general == 'review_count') {
                    $query = $query->withCount('reviews')->orderByDesc('reviews_count');
                } elseif ($popular_food_sort_by_general == 'order_count') {
                    $query = $query->orderByDesc('order_count');
                } elseif ($popular_food_sort_by_general == 'a_to_z') {
                    $query = $query->orderBy('name');
                } elseif ($popular_food_sort_by_general == 'z_to_a') {
                    $query = $query->orderByDesc('name');
                }

                $paginator = $query->paginate($limit, ['*'], 'page', $offset);

            }

            return [
                'total_size' => $paginator->total(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $paginator->items()
            ];
        }

        if ($popular_food_default_status == '1'){
            $paginator = Food::whereHas('restaurant', function($q)use($zone_id){
                $q->whereIn('zone_id', $zone_id)->Weekday();
            })->active()->type($type)->has('reviews')->popular()->limit(50)->get();
        }

        if ($popular_food_default_status == '0'){
            $time = Carbon::now()->toTimeString();

            $query = Food::Active()->with('restaurant')
                ->select(['food.*'])
                ->type($type)
                // ->leftJoin('restaurants', 'food.restaurant_id', '=', 'restaurants.id')
                ->whereHas('restaurant', function($q) use ($zone_id) {
                    $q->whereIn('zone_id', $zone_id)->Weekday();
                })
                ->selectSub(function ($subQuery) {
                    $subQuery->selectRaw('active as temp_available')
                        ->from('restaurants')
                        ->whereColumn('restaurants.id', 'food.restaurant_id');
                }, 'temp_available')
                ->selectSub(function ($subQuery) {
                    $subQuery->selectRaw('IF(((select count(*) from `restaurant_schedule` where `restaurants`.`id` = `restaurant_schedule`.`restaurant_id` and `restaurant_schedule`.`day` = ? and `restaurant_schedule`.`opening_time` < ? and `restaurant_schedule`.`closing_time` > ?) > 0), true, false) as open', [now()->dayOfWeek, now()->format('H:i:s'), now()->format('H:i:s')])
                        ->from('restaurants')
                        ->whereColumn('restaurants.id', 'food.restaurant_id');
                }, 'open');

            if($popular_food_sort_by_unavailable == 'remove'){
                $query = $query->available($time);
            }elseif($popular_food_sort_by_unavailable == 'last'){
                $query = $query->orderBy(DB::raw("CASE WHEN available_time_starts <= '$time' AND available_time_ends >= '$time' THEN 0 ELSE 1 END"));
            }

            if($popular_food_sort_by_temp_closed == 'remove'){
                $query = $query->having('temp_available', '>', 0);
            }elseif($popular_food_sort_by_temp_closed == 'last'){
                $query = $query->orderByDesc('temp_available');
            }

            if ($popular_food_sort_by_general == 'nearest_first') {
                $query = $query->selectSub(function ($subQuery) use ($longitude, $latitude) {
                    $subQuery->selectRaw('ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance', [$longitude, $latitude])
                        ->from('restaurants')
                        ->whereColumn('restaurants.id', 'food.restaurant_id');
                }, 'distance')
                    ->orderBy('distance');
            } elseif ($popular_food_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($popular_food_sort_by_general == 'review_count') {
                $query = $query->withCount('reviews')->orderByDesc('reviews_count');
            } elseif ($popular_food_sort_by_general == 'order_count') {
                $query = $query->orderByDesc('order_count');
            } elseif ($popular_food_sort_by_general == 'a_to_z') {
                $query = $query->orderBy('name');
            } elseif ($popular_food_sort_by_general == 'z_to_a') {
                $query = $query->orderByDesc('name');
            }

            $paginator = $query->limit(50)->get();

        }

        return [
            'total_size' => count($paginator),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator
        ];

    }


    public static function most_reviewed_products($zone_id, $limit = null, $offset = null, $type='all',$longitude=0,$latitude=0)
    {
        $best_reviewed_food_default_status = \App\Models\BusinessSetting::where('key', 'best_reviewed_food_default_status')->first();
        $best_reviewed_food_default_status = $best_reviewed_food_default_status ? $best_reviewed_food_default_status->value : 1;
        $best_reviewed_food_sort_by_general = \App\Models\PriorityList::where('name', 'best_reviewed_food_sort_by_general')->where('type','general')->first();
        $best_reviewed_food_sort_by_general = $best_reviewed_food_sort_by_general ? $best_reviewed_food_sort_by_general->value : '';
        $best_reviewed_food_sort_by_unavailable = \App\Models\PriorityList::where('name', 'best_reviewed_food_sort_by_unavailable')->where('type','unavailable')->first();
        $best_reviewed_food_sort_by_unavailable = $best_reviewed_food_sort_by_unavailable ? $best_reviewed_food_sort_by_unavailable->value : '';
        $best_reviewed_food_sort_by_temp_closed = \App\Models\PriorityList::where('name', 'best_reviewed_food_sort_by_temp_closed')->where('type','temp_closed')->first();
        $best_reviewed_food_sort_by_temp_closed = $best_reviewed_food_sort_by_temp_closed ? $best_reviewed_food_sort_by_temp_closed->value : '';
        $best_reviewed_food_sort_by_rating = \App\Models\PriorityList::where('name', 'best_reviewed_food_sort_by_rating')->where('type','rating')->first();
        $best_reviewed_food_sort_by_rating = $best_reviewed_food_sort_by_rating ? $best_reviewed_food_sort_by_rating->value : '';

        if($limit != null && $offset != null)
        {
            $query = Food::Active()->with('restaurant')
                ->select(['food.*'])
                // ->leftJoin('restaurants', 'food.restaurant_id', '=', 'restaurants.id')
                ->whereHas('restaurant', function($q) use ($zone_id) {
                    $q->whereIn('zone_id', $zone_id)->Weekday();
                })
                ->selectSub(function ($subQuery) {
                    $subQuery->selectRaw('active as temp_available')
                        ->from('restaurants')
                        ->whereColumn('restaurants.id', 'food.restaurant_id');
                }, 'temp_available')
                ->has('reviews')
                ->withCount('reviews')->type($type);

            if($best_reviewed_food_default_status == '1'){
                $query = $query->orderBy('reviews_count','desc');
            }else {
                $time = Carbon::now()->toTimeString();

                if ($best_reviewed_food_sort_by_unavailable == 'remove') {
                    $query = $query->available($time);
                } elseif ($best_reviewed_food_sort_by_unavailable == 'last') {
                    $query = $query->orderBy(DB::raw("CASE WHEN available_time_starts <= '$time' AND available_time_ends >= '$time' THEN 0 ELSE 1 END"));
                }

                if ($best_reviewed_food_sort_by_temp_closed == 'remove') {
                    $query = $query->having('temp_available', '>', 0);
                } elseif ($best_reviewed_food_sort_by_temp_closed == 'last') {
                    $query = $query->orderByDesc('temp_available');
                }

                if ($best_reviewed_food_sort_by_rating == 'four_plus') {
                    $query = $query->where('avg_rating','>',4);
                } elseif ($best_reviewed_food_sort_by_rating == 'three_half_plus') {
                    $query = $query->where('avg_rating','>',3.5);
                } elseif ($best_reviewed_food_sort_by_rating == 'three_plus') {
                    $query = $query->where('avg_rating','>',3);
                }

                if ($best_reviewed_food_sort_by_general == 'nearest_first') {
                    $query = $query->selectSub(function ($subQuery) use ($longitude, $latitude) {
                        $subQuery->selectRaw('ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance', [$longitude, $latitude])
                            ->from('restaurants')
                            ->whereColumn('restaurants.id', 'food.restaurant_id');
                    }, 'distance')
                        ->orderBy('distance');
                } elseif ($best_reviewed_food_sort_by_general == 'rating') {
                    $query = $query->orderByDesc('avg_rating');
                } elseif ($best_reviewed_food_sort_by_general == 'review_count') {
                    $query = $query->orderByDesc('reviews_count');
                }
            }

            $paginator = $query->paginate($limit, ['*'], 'page', $offset);

            return [
                'total_size' => $paginator->total(),
                'limit' => $limit,
                'offset' => $offset,
                'products' => $paginator->items()
            ];
        }
        $query = Food::Active()->with('restaurant')
            ->select(['food.*'])
            // ->leftJoin('restaurants', 'food.restaurant_id', '=', 'restaurants.id')
            ->whereHas('restaurant', function($q) use ($zone_id) {
                $q->whereIn('zone_id', $zone_id)->Weekday();
            })
            ->selectSub(function ($subQuery) {
                $subQuery->selectRaw('active as temp_available')
                    ->from('restaurants')
                    ->whereColumn('restaurants.id', 'food.restaurant_id');
            }, 'temp_available')
            ->has('reviews')
            ->withCount('reviews')->type($type);

        if($best_reviewed_food_default_status == '1'){
            $query = $query->orderBy('reviews_count','desc');
        }else {
            $time = Carbon::now()->toTimeString();

            if ($best_reviewed_food_sort_by_unavailable == 'remove') {
                $query = $query->available($time);
            } elseif ($best_reviewed_food_sort_by_unavailable == 'last') {
                $query = $query->orderBy(DB::raw("CASE WHEN available_time_starts <= '$time' AND available_time_ends >= '$time' THEN 0 ELSE 1 END"));
            }

            if ($best_reviewed_food_sort_by_temp_closed == 'remove') {
                $query = $query->having('temp_available', '>', 0);
            } elseif ($best_reviewed_food_sort_by_temp_closed == 'last') {
                $query = $query->orderByDesc('temp_available');
            }

            if ($best_reviewed_food_sort_by_rating == 'four_plus') {
                $query = $query->where('avg_rating','>',4);
            } elseif ($best_reviewed_food_sort_by_rating == 'three_half_plus') {
                $query = $query->where('avg_rating','>',3.5);
            } elseif ($best_reviewed_food_sort_by_rating == 'three_plus') {
                $query = $query->where('avg_rating','>',3);
            }

            if ($best_reviewed_food_sort_by_general == 'nearest_first') {
                $query = $query->selectSub(function ($subQuery) use ($longitude, $latitude) {
                    $subQuery->selectRaw('ST_Distance_Sphere(point(longitude, latitude), point(?, ?)) as distance', [$longitude, $latitude])
                        ->from('restaurants')
                        ->whereColumn('restaurants.id', 'food.restaurant_id');
                }, 'distance')
                    ->orderBy('distance');
            } elseif ($best_reviewed_food_sort_by_general == 'rating') {
                $query = $query->orderByDesc('avg_rating');
            } elseif ($best_reviewed_food_sort_by_general == 'review_count') {
                $query = $query->orderByDesc('reviews_count');
            }
        }
        $paginator = $query->limit(50)->get();

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator
        ];

    }

    public static function get_product_review($id)
    {
        $reviews = Review::where('product_id', $id)->get();
        return $reviews;
    }

    public static function get_rating($reviews)
    {
        $rating5 = 0;
        $rating4 = 0;
        $rating3 = 0;
        $rating2 = 0;
        $rating1 = 0;
        foreach ($reviews as $key => $review) {
            if ($review->rating == 5) {
                $rating5 += 1;
            }
            if ($review->rating == 4) {
                $rating4 += 1;
            }
            if ($review->rating == 3) {
                $rating3 += 1;
            }
            if ($review->rating == 2) {
                $rating2 += 1;
            }
            if ($review->rating == 1) {
                $rating1 += 1;
            }
        }
        return [$rating5, $rating4, $rating3, $rating2, $rating1];
    }

    public static function get_avg_rating($rating)
    {
        $total_rating = 0;
        $total_rating += $rating[1];
        $total_rating += $rating[2]*2;
        $total_rating += $rating[3]*3;
        $total_rating += $rating[4]*4;
        $total_rating += $rating[5]*5;

        return $total_rating/array_sum($rating);
    }

    public static function get_overall_rating($reviews)
    {
        $totalRating = count($reviews);
        $rating = 0;
        foreach ($reviews as $key => $review) {
            $rating += $review->rating;
        }
        if ($totalRating == 0) {
            $overallRating = 0;
        } else {
            $overallRating = number_format($rating / $totalRating, 2);
        }

        return [$overallRating, $totalRating];
    }

    public static function format_export_foods($foods)
    {
        $storage = [];
        foreach($foods as $item)
        {
            $category_id = 0;
            $sub_category_id = 0;
            foreach(json_decode($item->category_ids, true) as $key=>$category)
            {
                if($key==0)
                {
                    $category_id = $category['id'];
                }
                else if($key==1)
                {
                    $sub_category_id = $category['id'];
                }
            }
            $storage[] = [
                'Id'=>$item->id,
                'Name'=>$item->name,
                'Description'=>$item->description,
                'Image'=>$item->image,
                'CategoryId'=>$category_id,
                'SubCategoryId'=>$sub_category_id,
                'RestaurantId'=>$item->restaurant_id,
                'Price'=>$item->price,
                'Discount'=>$item->discount,
                'DiscountType'=>$item->discount_type,
                'AvailableTimeStarts'=>$item->available_time_starts,
                'AvailableTimeEnds'=>$item->available_time_ends,
                'Variations'=>$item->variations,
                // 'Addons'=>str_replace(['""','[',']'],'',$item->add_ons),
                'Addons'=>$item->add_ons,
                'Veg'=>$item->veg == 1 ? 'yes' :'no' ,
                'Recommended'=>$item->recommended == 1 ? 'yes' :'no' ,
                'Status'=>$item->status == 1 ? 'active' :'inactive' ,
                'AvgRating'=>round($item->avg_rating ,2),
                'TotalRatingCount'=>$item->rating_count,
                // 'Variations'=>str_replace(['{','}','[',']'],['(',')','',''],$item->variations),
                // 'attributes'=>str_replace(['"','[',']'],'',$item->attributes),
                // 'choice_options'=>str_replace(['{','}'],['(',')'],substr($item->choice_options, 1, -1)),
            ];
        }

        return $storage;
    }

    public static function update_food_ratings()
    {
        try{
            $foods = Food::withOutGlobalScopes()->whereHas('reviews')->with('reviews')->get();
            foreach($foods as $key=>$food)
            {
                $foods[$key]->avg_rating = $food?->reviews?->avg('rating');
                $foods[$key]->rating_count = $food?->reviews?->count();
                foreach($food->reviews as $review)
                {
                    $foods[$key]->rating = self::update_rating($foods[$key]->rating, $review->rating);
                }
                $foods[$key]->save();
            }
        }catch(\Exception $e){
            info($e->getMessage());
            return false;
        }
        return true;
    }

    public static function update_rating($ratings, $product_rating)
    {

        $restaurant_ratings = [1=>0 , 2=>0, 3=>0, 4=>0, 5=>0];
        if(isset($ratings))
        {
            $restaurant_ratings = json_decode($ratings, true);
            $restaurant_ratings[$product_rating] = $restaurant_ratings[$product_rating] + 1;
        }
        else
        {
            $restaurant_ratings[$product_rating] = 1;
        }
        return json_encode($restaurant_ratings);
    }



    public static function recommended_products($zone_id,$restaurant_id,$limit = null, $offset = null, $type='all',$name=null)
    {
        $data =[];
        if($limit != null && $offset != null)
        {
            $paginator = Food::where('restaurant_id', $restaurant_id)->whereHas('restaurant', function($q)use($zone_id){
                $q->whereIn('zone_id', $zone_id)->Weekday();
            })
            ->when(isset($name) , function($query)use($name){
                $query->where(function ($q) use ($name) {
                    foreach ($name as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('tags',function($query)use($name){
                        $query->where(function($q)use($name){
                            foreach ($name as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            };
                        });
                    });
                });
            })
            ->active()->type($type)->Recommended()->paginate($limit, ['*'], 'page', $offset);
                $data = $paginator->items();
        }
        else{
            $paginator = Food::where('restaurant_id', $restaurant_id)->active()->type($type)->whereHas('restaurant', function($q)use($zone_id){
                $q->whereIn('zone_id', $zone_id)->Weekday();
            })->Recommended()
            ->when(isset($name) , function($query)use($name){
                $query->where(function ($q) use ($name) {
                    foreach ($name as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('tags',function($query)use($name){
                        $query->where(function($q)use($name){
                            foreach ($name as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            };
                        });
                    });
                });
            })
            ->limit(50)->get();
            $data =$paginator;
        }

        return [
            'total_size' => $paginator->count(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $data
        ];
    }


    public static function format_export_addons($addons)
    {
        $storage = [];
        foreach($addons as $item)
        {
            $storage[] = [
                'Id'=>$item->id,
                'Name'=>$item->name,
                'Price'=>$item->price,
                'RestaurantId'=>$item->restaurant_id,
                'Status'=>$item->status == 1 ? 'active' : 'inactive'
            ];
        }

        return $storage;
    }

    public static function get_restaurant_popular_products($zone_id, $restaurant_id, $type='all',$name=null)
    {
        $products = Food::active()->type($type)->where('restaurant_id',$restaurant_id)->whereHas('restaurant', function($q)use($zone_id){
            $q->whereIn('zone_id', $zone_id)->Weekday();
        })->has('reviews')->popular()
        ->when(isset($name) , function($query)use($name){
            $query->where(function ($q) use ($name) {
                foreach ($name as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
                $q->orWhereHas('tags',function($query)use($name){
                    $query->where(function($q)use($name){
                        foreach ($name as $value) {
                            $q->where('tag', 'like', "%{$value}%");
                        };
                    });
                });
            });
        })
        ->limit(50)->get();
        return  $products;
    }

    public static function recommended_most_reviewed($zone_id,$restaurant_id=null, $limit = null, $offset = null, $type='all',$name =null )
    {
            $paginator = Food::where('restaurant_id',$restaurant_id)->whereHas('restaurant', function($q)use($zone_id){
                $q->whereIn('zone_id', $zone_id)->Weekday();
            })
            ->has('reviews')->withCount('reviews')
            ->orderBy('reviews_count','desc')
            ->orwhere(function($query) use($restaurant_id){
                $query->where('recommended',1)->where('restaurant_id',$restaurant_id);
            })
            ->orderBy('recommended','desc')
            ->when(isset($name) , function($query)use($name){
                $query->where(function ($q) use ($name) {
                    foreach ($name as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('tags',function($query)use($name){
                        $query->where(function($q)use($name){
                            foreach ($name as $value) {
                                $q->where('tag', 'like', "%{$value}%");
                            };
                        });
                    });
                });
            })
            ->active()->type($type)
            ->limit(50)->get();
            return  $paginator;

    }
}
