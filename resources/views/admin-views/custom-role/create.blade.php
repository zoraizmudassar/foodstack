@extends('layouts.admin.app')
@section('title',translate('messages.Custom_Role'))
@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">

    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="page-header-title mb-2 text-capitalize">
            <div class="card-header-icon d-inline-flex mr-2 img">
                <img src="{{dynamicAsset('/public/assets/admin/img/role.png')}}" alt="public">
            </div>
            <span>
                {{translate('messages.Employee_Role')}}
            </span>
        </h1>
    </div>
    <!-- Content Row -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{route('admin.custom-role.create')}}" method="post">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        @php($language=\App\Models\BusinessSetting::where('key','language')->first())
                        @php($language = $language->value ?? null)
                        @php($default_lang = str_replace('_', '-', app()->getLocale()))
                        @if ($language)
                                <div class="js-nav-scroller hs-nav-scroller-horizontal">
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link lang_link active"
                                href="#"
                                id="default-link">{{translate('messages.default')}}</a>
                            </li>
                            @foreach (json_decode($language) as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link"
                                        href="#"
                                        id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                </li>
                            @endforeach
                        </ul>
                                </div>
                        @endif
                        <input type="hidden" name="lang[]" value="default">

                        <div class="form-group lang_form" id="default-form">
                            <label class="form-label input-label " for="name">{{ translate('messages.role_name') }} ({{ translate('messages.default') }})</label>
                            <input type="text" name="name[]" class="form-control" placeholder="{{translate('role_name_example')}}" maxlength="191"   >
                        </div>

                        @if ($language)
                            @foreach(json_decode($language) as $lang)
                                <div class="form-group d-none lang_form" id="{{$lang}}-form">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.role_name')}} ({{strtoupper($lang)}})</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="{{translate('role_name_example')}}" maxlength="191"  >
                                </div>
                                <input type="hidden" name="lang[]" value="{{$lang}}">
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="d-flex">
                    <h5 class="input-label m-0 text-capitalize">{{translate('messages.module_permission')}} : </h5>
                    <div class="check-item pb-0 w-auto">
                        <div class="form-group form-check form--check m-0 ml-2">
                            <input type="checkbox" name="modules[]" value="account" class="form-check-input"
                                    id="select-all">
                            <label class="form-check-label ml-2" for="select-all">{{ translate('Select_All') }}</label>
                        </div>
                    </div>
                </div>
                <div class="check--item-wrapper">
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="account" class="form-check-input"
                                    id="account">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="account">{{translate('messages.collect_cash')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="addon" class="form-check-input"
                                    id="addon">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="addon">{{translate('messages.addon')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="banner" class="form-check-input"
                                    id="banner">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="banner">{{translate('messages.banner')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="campaign" class="form-check-input"
                                    id="campaign">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="campaign">{{translate('messages.campaign')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="category" class="form-check-input"
                                    id="category">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="category">{{translate('messages.category')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="coupon" class="form-check-input"
                                    id="coupon">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="coupon">{{translate('messages.coupon')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="customerList" class="form-check-input"
                                    id="customerList">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="customerList">{{translate('messages.customers_section')}}</label>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="customer_wallet" class="form-check-input"
                                    id="customer_wallet">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="customer_wallet">{{translate('messages.customer_Wallet')}}</label>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="deliveryman" class="form-check-input"
                                    id="deliveryman">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="deliveryman">{{translate('messages.deliveryman')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="provide_dm_earning" class="form-check-input"
                                    id="provide_dm_earning">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="provide_dm_earning">{{translate('messages.deliverymen_earning_provide')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="employee" class="form-check-input"
                                    id="employee">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="employee">{{translate('messages.Employee')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="food" class="form-check-input"
                                    id="food">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="food">{{translate('messages.food')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="notification" class="form-check-input"
                                    id="notification">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="notification">{{translate('messages.push_notification')}} </label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="order" class="form-check-input"
                                    id="order">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="order">{{translate('messages.order')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="restaurant" class="form-check-input"
                                    id="restaurant">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="restaurant">{{translate('messages.restaurants')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="report" class="form-check-input"
                                    id="report">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="report">{{translate('messages.report')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="settings" class="form-check-input"
                                    id="settings">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="settings">{{translate('messages.business_settings')}}</label>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="withdraw_list" class="form-check-input"
                                    id="withdraw_list">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="withdraw_list">{{translate('messages.restaurant_withdraws')}}</label>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="pos" class="form-check-input"
                                    id="pos">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="pos">{{translate('messages.pos_system')}}</label>
                        </div>
                    </div>

                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="zone" class="form-check-input"
                                    id="zone">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="zone">{{translate('messages.zone')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="contact_message" class="form-check-input"
                                    id="contact_message">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="contact_message">{{translate('messages.contact_messages')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="disbursement" class="form-check-input"
                                    id="disbursement">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="disbursement">{{translate('messages.disbursement')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="advertisement" class="form-check-input"
                                    id="advertisement">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="advertisement">{{translate('messages.advertisement')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="cashback" class="form-check-input"
                                    id="cashback">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="cashback">{{translate('messages.cashback')}}</label>
                        </div>
                    </div>
                    <div class="check-item">
                        <div class="form-group form-check form--check">
                            <input type="checkbox" name="modules[]" value="chat" class="form-check-input"
                                    id="chat">
                            <label class="form-check-label ml-2 ml-sm-3  text-dark" for="chat">{{translate('messages.chat')}}</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pb-3">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn" class="btn btn--reset">
                            {{translate('messages.reset')}}
                        </button>
                        <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 border-0">
            <div class="search--button-wrapper">
                <h5 class="card-title">
                    {{translate('messages.employee_Role_Table')}}
                    <span class="badge badge-soft-dark ml-2" id="itemCount">{{$rl->total()}}</span>
                </h5>

                <form action="javascript:" id="search-form">
                    @csrf
                    <!-- Search -->
                    <div class="input--group input-group input-group-merge input-group-flush">
                        <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{translate('messages.Search_by_Name')}}" aria-label="Search">
                        <button type="submit" class="btn btn--secondary">
                            <i class="tio-search"></i>
                        </button>
                    </div>
                    <!-- End Search -->
                </form>

                <div class="hs-unfold ml-3">
                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle btn export-btn btn-outline-primary btn--primary font--sm" href="javascript:;"
                        data-hs-unfold-options='{
                            "target": "#usersExportDropdown",
                            "type": "css-animation"
                        }'>
                        <i class="tio-download-to mr-1"></i>
                        {{translate('messages.export')}}
                    </a>

                    <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                        <span class="dropdown-header">{{translate('messages.download_options')}}</span>
                        <a id="export-excel" class="dropdown-item" href="{{route('admin.custom-role.export-employee-role', ['type'=>'excel'])}}">
                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{dynamicAsset('public/assets/admin')}}/svg/components/excel.svg"
                                    alt="Image Description">
                            {{translate('messages.excel')}}
                        </a>
                        <a id="export-csv" class="dropdown-item" href="{{route('admin.custom-role.export-employee-role', ['type'=>'excel'])}}">
                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{dynamicAsset('public/assets/admin')}}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                            {{translate('messages.csv')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false
                        }'>
                    <thead class="thead-light">
                    <tr>
                        <th scope="col" class="w-50px">{{ translate('messages.sl') }}</th>
                        <th scope="col" class="w-50px">{{ translate('Role_Name') }}</th>
                        <th scope="col" class="w-200px">{{translate('messages.modules')}}</th>
                        <th scope="col" class="w-50px">{{translate('messages.created_at')}}</th>
                        <th scope="col" class="text-center w-50px">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>
                    <tbody  id="set-rows">
                    @foreach($rl as $k=>$r)
                        <tr>
                            <td>{{$k+$rl->firstItem()}}</td>
                            <td>{{Str::limit($r['name'],25,'...')}}</td>
                            <td>
                                <div class="text-capitalize" data-toggle="tooltip" data-placement="right" title="@if($r['modules']!=null)
                                @foreach((array)json_decode($r['modules']) as $key=>$m)
                                {{translate(str_replace('_',' ',$m)) }}{{ !$loop->last ? ',' : '.'}}
                                @endforeach
                            @endif" >
                                    @if($r['modules']!=null)
                                        @foreach((array)json_decode($r['modules']) as $key=>$m)
                                            {{translate(str_replace('_',' ',$m)) }}{{ !$loop->last ? ',' : '.'}}
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td>
                                {{   \App\CentralLogics\Helpers::date_format($r['created_at']) }}
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn btn--primary btn-outline-primary action-btn"
                                        href="{{route('admin.custom-role.edit',[$r['id']])}}" title="{{translate('messages.edit_role')}}"><i class="tio-edit"></i>
                                    </a>
                                    <a class="btn btn--danger btn-outline-danger action-btn form-alert" href="javascript:"
                                        data-id="role-{{$r['id']}}" data-message="{{translate('messages.Want_to_delete_this_role_?')}}" title="{{translate('messages.delete_role')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                </div>
                                <form action="{{route('admin.custom-role.delete',[$r['id']])}}"
                                        method="post" id="role-{{$r['id']}}">
                                    @csrf @method('delete')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @if(count($rl) === 0)
                <div class="empty--data">
                    <img src="{{dynamicAsset('/public/assets/admin/img/empty.png')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
                @endif
                <div class="page-area px-4 pb-3">
                    <div class="d-flex align-items-center justify-content-end">
                        <div>
                {!! $rl->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('script_2')
    <script>
        "use strict";
        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.custom-role.search')}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    $('#set-rows').html(data.view);
                    $('#itemCount').html(data.count);
                    $('.page-area').hide();
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });
        $(document).ready(function() {
            let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));
        });

        $('#reset_btn').click(function(){
            location.reload(true);
        })

    $('#select-all').on('change', function(){
        if(this.checked === true) {
            $('.check--item-wrapper .check-item .form-check-input').attr('checked', true)
        } else {
            $('.check--item-wrapper .check-item .form-check-input').attr('checked', false)
        }
    })

    $('.check--item-wrapper .check-item .form-check-input').on('change', function(){
            if(this.checked === true) {
                $(this).attr('checked', true)
            } else {
                $(this).attr('checked', false)
            }
        })
    </script>

@endpush
