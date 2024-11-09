@extends('layouts.admin.app')

@section('title',translate('messages.add_new_addon'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h2 class="page-header-title text-capitalize">
                        <div class="card-header-icon d-inline-flex mr-2 img">
                            <img src="{{dynamicAsset('/public/assets/admin/img/addon.png')}}" alt="public">
                        </div>
                        <span>
                            {{translate('messages.add_new_addon')}}
                        </span>
                    </h2>
                </div>
                @if(isset($addon))
                <a href="{{route('admin.addon.add-new')}}" class="btn btn--primary pull-right"><i class="tio-add-circle"></i> {{translate('messages.add_new_addon')}}</a>
                @endif
            </div>
        </div>
        <!-- End Page Header -->
        @php($language=\App\Models\BusinessSetting::where('key','language')->first())
        @php($language = $language->value ?? null)
        @php($default_lang = str_replace('_', '-', app()->getLocale()))
        <div class="card">
            <div class="card-body">
                <form action="{{isset($addon)?route('admin.addon.update',[$addon['id']]):route('admin.addon.store')}}" method="post">
                    @csrf
                    @if($language)
                        @php($default_lang = json_decode($language)[0])
                                <div class="js-nav-scroller hs-nav-scroller-horizontal">
                        <ul class="nav nav-tabs mb-4">
                            <li class="nav-item">
                                <a class="nav-link lang_link active" href="#" id="default-link">{{ translate('Default')}}</a>
                            </li>
                            @foreach(json_decode($language) as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link" href="#" id="{{$lang}}-link">{{\App\CentralLogics\Helpers::get_language_name($lang).'('.strtoupper($lang).')'}}</a>
                                </li>
                            @endforeach
                        </ul>
                                </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group lang_form" id="default-form">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}}</label>
                                <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.Ex_:_water') }}"   maxlength="191">
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                        @if ($language)
                            @foreach(json_decode($language) as $lang)
                                <div class="form-group d-none lang_form" id="{{$lang}}-form">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}} ({{strtoupper($lang)}})</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.Ex_:_water') }}" maxlength="191"  >
                                </div>
                                <input type="hidden" name="lang[]" value="{{$lang}}">
                            @endforeach
                        @else
                            <div class="form-group lang_form" id="default-form">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}}</label>
                                <input type="text" name="name[]" class="form-control" placeholder="{{ translate('messages.Ex_:_water') }}"  maxlength="191">
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                        @endif
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlSelect1">{{translate('messages.restaurant')}}<span
                                        class="input-label-secondary"></span></label>

                                <select name="restaurant_id" id="restaurant_id" class="js-data-example-ajax form-control"  data-placeholder="{{translate('messages.select_restaurant')}}" oninvalid="this.setCustomValidity('{{translate('messages.please_select_restaurant')}}')">
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.price')}}</label>
                                <input type="number" min="0" max="999999999999.99" name="price" step="0.01" value="{{old('price')}}" class="form-control" placeholder="100" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group mb-0">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.Stock_Type') }}
                                </label>
                                <select name="stock_type" id="stock_type" class="form-control js-select2-custom">
                                    <option value="unlimited">{{ translate('messages.Unlimited_Stock') }}</option>
                                    <option value="limited">{{ translate('messages.Limited_Stock')  }}</option>
                                    <option value="daily">{{ translate('messages.Daily_Stock')  }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 hide_this">
                            <div class="form-group">
                                <label class="input-label" for="addon_stock">{{translate('messages.Addon_Stock')}}</label>
                                <input type="number" min="0" id="addon_stock" max="999999999999" name="addon_stock"  readonly value="{{old('addon_stock')}}" class="form-control stock_disable" placeholder="{{ translate('messages.Unlimited') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <div class="btn--container justify-content-end">
                            <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>

                            <button type="submit" class="btn btn--primary">{{isset($addon)?translate('messages.update'):translate('messages.submit')}}</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
        <div class="card mt-2">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title"> {{translate('messages.addon_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$addons->total()}}</span></h5>
                    <div class="mr-sm-3">
                        <select name="restaurant_id" id="restaurant" data-placeholder="{{translate('messages.select_restaurant')}}" class="js-data-example-ajax form-control set-filter"
                        data-url="{{url()->full()}}" data-filter="restaurant_id"   title="Select Restaurant">
                            @if(isset($restaurant))
                            <option value="{{$restaurant->id}}" selected>{{$restaurant->name}}</option>
                            @else
                            <option value="all" selected>{{translate('messages.all_restaurants')}}</option>
                            @endif
                        </select>
                    </div>
                    <form>
                        <!-- Search -->
                        <div class="input--group input-group input-group-merge input-group-flush">
                            <input id="datatableSearch" type="search" name="search" value="{{ request()?->search ?? null }}" class="form-control" placeholder="{{translate('Search_by_name')}}" aria-label="Search addons">
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
                            <i class="tio-download-to mr-1"></i> {{translate('messages.export')}}
                        </a>

                        <div id="usersExportDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            <span class="dropdown-header">{{translate('messages.download_options')}}</span>
                            <a target="__blank" id="export-excel" class="dropdown-item" href="{{route('admin.addon.export_addons', ['type'=>'excel', request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{dynamicAsset('public/assets/admin')}}/svg/components/excel.svg"
                                        alt="Image Description">
                                {{translate('messages.excel')}}
                            </a>
                            <a target="__blank" id="export-csv" class="dropdown-item" href="{{route('admin.addon.export_addons', ['type'=>'csv' ,request()->getQueryString()])}}">
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
                    <table id="datatable" class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"  data-hs-datatables-options='{
                        "search": "#datatableSearch",
                        "entries": "#datatableEntries",
                        "isResponsive": false,
                        "isShowPaging": false,
                        "paging":false
                            }'>
                        <thead class="thead-light">
                        <tr>
                            <th>{{translate('sl')}}</th>
                            <th class="w-20p">{{translate('messages.name')}}</th>
                            <th class="w-20p">{{translate('messages.price')}}</th>
                            <th class="w-26p">{{translate('messages.restaurant')}}</th>
                            <th class="w-20p">{{translate('messages.Stock_Type')}}</th>
                            <th class="w-20p">{{translate('messages.Stock')}}</th>
                            {{-- <th class="w-26p">{{translate('messages.Available_stock')}}</th> --}}
                            <th class="w-12p">{{translate('messages.status')}}</th>
                            <th class="text-center w-12p">{{translate('messages.action')}}</th>
                        </tr>
                        </thead>

                        <tbody id="set-rows">
                        @foreach($addons as $key=>$addon)
                            <tr>
                                <td>{{$key+ $addons->firstItem()}}</td>
                                <td>
                                <span class="d-block font-size-sm text-body">
                                    {{Str::limit($addon['name'],20,'...')}}
                                </span>
                                </td>
                                <td>
                                    <div class="max-130px">
                                        {{\App\CentralLogics\Helpers::format_currency($addon['price'])}}
                                    </div>
                                </td>
                                <td  class="pl-3">{{Str::limit($addon->restaurant?$addon->restaurant->name:translate('messages.restaurant_deleted'),25,'...')}}</td>
                                <td>
                                    {{ translate($addon->stock_type) }}
                                </td>
                                <td>
                                    {{  $addon->stock_type == 'unlimited'? translate('messages.Unlimited') :  $addon->addon_stock }}
                                </td>
                                {{-- <td>
                                    {{  $addon->stock_type == 'unlimited'? translate('messages.Unlimited') :  $addon->addon_stock - $addon->sell_count }}
                                </td> --}}


                                <td>
                                    <label class="toggle-switch toggle-switch-sm" for="stausCheckbox{{$addon->id}}">
                                    <input type="checkbox" data-url="{{route('admin.addon.status',[$addon['id'],$addon->status?0:1])}}" class="toggle-switch-input redirect-url" id="stausCheckbox{{$addon->id}}" {{$addon->status?'checked':''}}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>


                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn btn-sm btn--primary btn-outline-primary action-btn"
                                        href="{{route('admin.addon.edit',[$addon['id']])}}" title="{{translate('messages.edit_addon')}}"><i class="tio-edit"></i></a>
                                        <a class="btn btn-sm btn--danger btn-outline-danger action-btn form-alert"     href="javascript:"
                                           data-id="addon-{{$addon['id']}}" data-message="{{ translate('Want to delete this addon') }}" title="{{translate('messages.delete_addon')}}"><i class="tio-delete-outlined"></i></a>
                                        <form action="{{route('admin.addon.delete',[$addon['id']])}}"
                                                    method="post" id="addon-{{$addon['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @if(count($addons) === 0)
                    <div class="empty--data">
                        <img src="{{dynamicAsset('/public/assets/admin/img/empty.png')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer p-0 border-0">
                <!-- Pagination -->
                <div class="page-area px-4 pb-3">
                    <div class="d-flex align-items-center justify-content-end">
                        <div>
                            {!! $addons->links() !!}
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
        $('#stock_type').on('change', function () {
            stock_type($(this).val());
        });

        stock_type($('#stock_type').val());
    function  stock_type(data){
        if(data == 'unlimited') {
                    $('.stock_disable').prop('readonly', true).prop('required', false).attr('placeholder', '{{ translate('Unlimited') }}').val('');
                    $('.hide_this').addClass('d-none');
                } else {
                    $('.stock_disable').prop('readonly', false).prop('required', true).attr('placeholder', '{{ translate('messages.Ex:_100') }}');
                     $('.hide_this').removeClass('d-none');
                }
    }


        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
            select: {
                style: 'multi',
                classMap: {
                checkAll: '#datatableCheckAll',
                counter: '#datatableCounter',
                counterInfo: '#datatableCounterInfo'
                }
          },
          language: {
            zeroRecords: '<div class="text-center p-4">' +
                '<img class="w-7rem mb-3" src="{{dynamicAsset('public/assets/admin/svg/illustrations/sorry.svg')}}" alt="Image Description">' +
                '<p class="mb-0">{{ translate('No_data_to_show') }}</p>' +
                '</div>'
          }
        });

        $('#datatableSearch').on('mouseup', function (e) {
          let $input = $(this),
            oldValue = $input.val();

          if (oldValue == "") return;

          setTimeout(function(){
            let newValue = $input.val();

            if (newValue == ""){
              // Gotcha
              datatable.search('').draw();
            }
          }, 1);
        });

        $('#toggleColumn_index').change(function (e) {
          datatable.columns(0).visible(e.target.checked)
        })
        $('#toggleColumn_name').change(function (e) {
          datatable.columns(1).visible(e.target.checked)
        })

        $('#toggleColumn_vendor').change(function (e) {
          datatable.columns(3).visible(e.target.checked)
        })

        $('#toggleColumn_status').change(function (e) {
          datatable.columns(4).visible(e.target.checked)
        })
        $('#toggleColumn_price').change(function (e) {
          datatable.columns(2).visible(e.target.checked)
        })
        $('#toggleColumn_action').change(function (e) {
          datatable.columns(5).visible(e.target.checked)
        })


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#restaurant').select2({
            ajax: {
                url: '{{url('/')}}/admin/restaurant/get-restaurants',
                data: function (params) {
                    return {
                        q: params.term, // search term
                        all:true,
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                    results: data
                    };
                },
                __port: function (params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        $('#restaurant_id').select2({
            ajax: {
                url: '{{url('/')}}/admin/restaurant/get-restaurants',
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                    results: data
                    };
                },
                __port: function (params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });


        $('#reset_btn').click(function(){
            $('#restaurant_id').val(null).trigger('change');
            $('#stock_type').val('unlimited').trigger('change');
            stock_type('unlimited');
        })
    </script>
@endpush
