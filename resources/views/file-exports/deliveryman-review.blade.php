<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('Deliveryman_Review_List') }}</h1></div>
    <div class="col-lg-12">



    <table>
        <thead>
            <tr>
                <th>{{ translate('Search_Criteria') }}</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('Search_Bar_Content')  }}- {{ $data['search'] ??translate('N/A') }}

                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
                </tr>
        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{translate('messages.Deliveryman_Name')}}</th>
            <th>{{translate('messages.Order_Id')}}</th>
            <th>{{translate('messages.Customer_Name')}}</th>
            <th>{{translate('messages.Restaurant_Name')}}</th>
            <th>{{translate('messages.Rating')}}</th>
            <th>{{translate('messages.Review')}}</th>
        </thead>
        <tbody>
        @foreach($data['reviews'] as $key => $review)
            <tr>
                <td>{{ $key+1}}</td>
                <td>{{$review->delivery_man->f_name.' '.$review->delivery_man->l_name}}</td>
                <td>
                    {{ $review->order_id }}
                </td>
                <td>
                    @if ($review->customer)
                        {{$review->customer?$review->customer->f_name:""}} {{$review->customer?$review->customer->l_name:""}}
                    @else
                        {{translate('messages.customer_not_found')}}
                    @endif
                </td>
                <td>
                    {{$review->order?->restaurant?->name}}
                </td>
                <td>{{ $review->rating }}</td>
                <td>{{ $review->comment  ?? translate('N/A') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
