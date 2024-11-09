@extends('layouts.vendor.app')

@section('title',translate('messages.foods_bulk_import'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title mb-2 text-capitalize">
                <div class="card-header-icon d-inline-flex mr-2 img">
                    <img src="{{dynamicAsset('public/assets/admin/img/export.png')}}" alt="">
                </div>
                {{translate('messages.foods_bulk_import')}}
            </h1>
        </div>
        <div class="card mb-2">
            <div class="card-body">
                <div class="export-steps style-2">
                    <div class="export-steps-item">
                        <div class="inner">
                            <h5>{{ translate('STEP_1') }}</h5>
                            <p>
                                {{ translate('Download_Excel_File') }}
                            </p>
                        </div>
                    </div>
                    <div class="export-steps-item">
                        <div class="inner">
                            <h5>{{ translate('STEP_2') }}</h5>
                            <p>
                                {{ translate('Match_Spread_sheet_data_according_to_instruction') }}
                            </p>
                        </div>
                    </div>
                    <div class="export-steps-item">
                        <div class="inner">
                            <h5>{{ translate('STEP_3') }}</h5>
                            <p>
                                {{ translate('Validate_data_and_and_complete_import') }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="jumbotron pt-1 pb-4 mb-0 bg-white">
                    <h2 class="mb-3 text-primary">{{ translate('Instructions') }}</h2>
                    <p>{{ translate('1._Download_the_format_file_and_fill_it_with_proper_data.') }}</p>

                    <p>{{ translate('2._You_can_download_the_example_file_to_understand_how_the_data_must_be_filled.') }}</p>

                    <p>{{ translate('3._Once_you_have_downloaded_and_filled_the_format_file,_upload_it_in_the_form_below_and_submit.') }}</p>

                    <p> {{ translate('4._After_uploading_foods_you_need_to_edit_them_and_set_image_and_variations.') }}</p>

                    <p> {{ translate('5._You_can_get_category_id_from_their_list,_please_input_the_right_ids.') }}</p>

                    <p> {{ translate('6._Don`t_forget_to_fill_all_the_fields') }} </p>

                    <p>{{ translate('7._For_veg_food_enter_1_and_for_non-veg_enter_0_on_veg_field.') }}</p>

                </div>
                <div class="text-center pb-4">
                    <h3 class="mb-3 export--template-title">{{ translate('Download Spreadsheet Template') }}</h3>
                    <div class="btn--container justify-content-center export--template-btns">
                        <a href="{{dynamicAsset('public/assets/restaurant_panel/foods_bulk_format.xlsx')}}" download="" class="btn btn-dark">{{ translate('Template_with_Existing_Data') }}</a>
                        <a href="{{dynamicAsset('public/assets/restaurant_panel/foods_bulk_format_nodata.xlsx')}}" download="" class="btn btn-dark">{{ translate('Template_without_Data') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <form class="product-form" id="import_form" action="{{route('vendor.food.bulk-import')}}" method="POST"
                enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="button" id="btn_value">
            <div class="card rest-part">
                <div class="card-body">
                    <h4 class="mb-3 mt-2">{{ translate('Import Restaurants') }}</h4>
                    <div class="custom-file custom--file">
                        <input type="file" name="products_file" class="form-control" id="bulk__import">
                        <label class="custom-file-label" for="bulk__import">{{ translate('Choose File') }}</label>
                    </div>
                    <div class="btn--container justify-content-end mt-3">
                        <button id="reset_btn" type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" name="button" value="update" class="btn btn--warning submit_btn">{{translate('messages.update')}}</button>
                        <button type="submit" name="button" value="import" class="btn btn--primary submit_btn">{{translate('messages.Import')}}</button>
                    </div>
                </div>
            </div>
        </form>



        <form action="javascript:" method="post" id="item_form" enctype="multipart/form-data">
            @csrf
            <div id="food_variation_section" >
                <div class="card mt-2 rest-part">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span class="card-header-icon">
                            </span>
                            <span>{{ translate('messages.food_variations_generator') }}</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <div id="add_new_option">
                                </div>
                                <br>
                                <div class="mt-2">
                                    <a class="btn btn-outline-success"
                                        id="add_new_option_button">{{ translate('add_new_variation') }}</a>
                                </div> <br><br>
                            </div>
                        </div>
                        <div class="btn--container justify-content-end mb-3">
                            <button type="submit" class="btn btn--primary">{{translate('generate')}}</button>
                        </div>
                        <textarea name="" id="food_variation_outpot" class="form-control" rows="5" readonly></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script_2')
<script>
    "use strict";
    let count = 0;
    let countRow = 0;
    let element = 0;
    $(document).ready(function() {
        $("#add_new_option_button").click(function(e) {
            count++;
            let add_option_view = `
                <div class="card view_new_option mb-2" >
                    <div class="card-header">
                        <label for="" id=new_option_name_` + count + `> {{ translate('add_new') }}</label>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-lg-3 col-md-6">
                                <label for="">{{ translate('name') }}</label>
                                 <input required name=options[` + count +
                `][name] class="form-control new_option_name" type="text" data-count="`+
                count +`">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <div class="form-group">
                                    <label class="input-label text-capitalize d-flex alig-items-center"><span class="line--limit-1">{{ translate('messages.selcetion_type') }} </span>
                                    </label>
                                    <div class="resturant-type-group border">
                                        <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input show_min_max" data-count="`+count+`" type="radio" value="multi"
                                                name="options[` + count + `][type]" id="type` + count +
                `" checked
                                                >
                                                <span class="form-check-label">
                                                    {{ translate('Multiple Selection') }}
                </span>
            </label>

            <label class="form-check form--check mr-2 mr-md-4">
                <input class="form-check-input hide_min_max" data-count="`+count+`" type="radio" value="single"
                                                name="options[` + count + `][type]" id="type` + count +
                `"
                                                >
                                                <span class="form-check-label">
                                                    {{ translate('Single Selection') }}
                </span>
            </label>
    </div>
</div>
</div>
<div class="col-12 col-lg-6">
<div class="row g-2">
    <div class="col-sm-6 col-md-4">
        <label for="">{{ translate('Min') }}</label>
                                        <input id="min_max1_` + count + `" required  name="options[` + count + `][min]" class="form-control" type="number" min="1">
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                        <label for="">{{ translate('Max') }}</label>
                                        <input id="min_max2_` + count + `"   required name="options[` + count + `][max]" class="form-control" type="number" min="1">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="d-md-block d-none">&nbsp;</label>
                                            <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <input id="options[` + count + `][required]" name="options[` +
                count + `][required]" type="checkbox">
                                                <label for="options[` + count + `][required]" class="m-0">{{ translate('Required') }}</label>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-danger btn-sm delete_input_button"
                                                    title="{{ translate('Delete') }}">
                                                    <i class="tio-add-to-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="option_price_` + count + `" >
                            <div class="border rounded p-3 pb-0 mt-3">
                                <div  id="option_price_view_` + count + `">
                                    <div class="row g-3 add_new_view_row_class mb-3">
                                        <div class="col-md-4 col-sm-6">
                                            <label for="">{{ translate('Option_name') }}</label>
                                            <input class="form-control" required type="text" name="options[` +
                count +
                `][values][0][label]" id="">
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <label for="">{{ translate('Additional_price') }}</label>
                                            <input class="form-control" required type="number" min="0" step="0.01" name="options[` +
                count + `][values][0][optionPrice]" id="">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 p-3 mr-1 d-flex "  id="add_new_button_` + count +
                `">
                                   <button type="button" class="btn btn--primary btn-outline-primary add_new_row_button" data-count="`+
                count +`" >{{ translate('Add_New_Option') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            $("#add_new_option").append(add_option_view);
        });
    });

    function show_min_max(data) {
        $('#min_max1_' + data).removeAttr("readonly");
        $('#min_max2_' + data).removeAttr("readonly");
        $('#min_max1_' + data).attr("required", "true");
        $('#min_max2_' + data).attr("required", "true");
    }

    function hide_min_max(data) {
        $('#min_max1_' + data).val(null).trigger('change');
        $('#min_max2_' + data).val(null).trigger('change');
        $('#min_max1_' + data).attr("readonly", "true");
        $('#min_max2_' + data).attr("readonly", "true");
        $('#min_max1_' + data).attr("required", "false");
        $('#min_max2_' + data).attr("required", "false");
    }

    $(document).on('change', '.show_min_max', function () {
        let data = $(this).data('count');
        show_min_max(data);
    });

    $(document).on('change', '.hide_min_max', function () {
        let data = $(this).data('count');
        hide_min_max(data);
    });


    function new_option_name(value, data) {
        $("#new_option_name_" + data).empty();
        $("#new_option_name_" + data).text(value)
        console.log(value);
    }

    function removeOption(e) {
        element = $(e);
        element.parents('.view_new_option').remove();
    }

    function deleteRow(e) {
        element = $(e);
        element.parents('.add_new_view_row_class').remove();
    }


    $(document).on('click', '.delete_input_button', function () {
        let e = $(this);
        removeOption(e);
    });
    $(document).on('click', '.deleteRow', function () {
        let e = $(this);
        deleteRow(e);
    });
    function add_new_row_button(data) {
        count = data;
        countRow = 1 + $('#option_price_view_' + data).children('.add_new_view_row_class').length;
        let add_new_row_view = `
        <div class="row add_new_view_row_class mb-3 position-relative pt-3 pt-sm-0">
            <div class="col-md-4 col-sm-5">
                    <label for="">{{ translate('Option_name') }}</label>
                    <input class="form-control" required type="text" name="options[` + count + `][values][` +
            countRow + `][label]" id="">
                </div>
                <div class="col-md-4 col-sm-5">
                    <label for="">{{ translate('Additional_price') }}</label>
                    <input class="form-control"  required type="number" min="0" step="0.01" name="options[` +
            count +
            `][values][` + countRow + `][optionPrice]" id="">
                </div>
                <div class="col-sm-2 max-sm-absolute">
                    <label class="d-none d-sm-block">&nbsp;</label>
                    <div class="mt-1">
                        <button type="button" class="btn btn-danger btn-sm deleteRow"
                            title="{{ translate('Delete') }}">
                            <i class="tio-add-to-trash"></i>
                        </button>
                    </div>
            </div>
        </div>`;
        $('#option_price_view_' + data).append(add_new_row_view);

    }

    $(document).on('click', '.add_new_row_button', function () {
        let data = $(this).data('count');
        add_new_row_button(data);
    });

    $(document).on('keyup', '.new_option_name', function () {
        let data = $(this).data('count');
        let value = $(this).val();
        new_option_name(value, data);
    });

    $('#item_form').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post({
            url: '{{ route('vendor.food.food-variation-generate') }}',
            data: $('#item_form').serialize(),
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#loading').show();
            },
            success: function(data) {
                $('#loading').hide();
                if (data.errors) {
                    for (let i = 0; i < data.errors.length; i++) {
                        toastr.error(data.errors[i].message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                } else {
                    $('#food_variation_outpot').val(data.variation)
                }
            }
        });
    });


    $(document).on("click", ".submit_btn", function(e){
        e.preventDefault();
            let data = $(this).val();
            myFunction(data)
    });


    function myFunction(data) {
        Swal.fire({
        title: '{{ translate('Are_you_sure?') }}' ,
        text: "{{ translate('You_want_to_') }}" +data,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: '{{ translate('no') }}',
        confirmButtonText: '{{ translate('yes') }}',
        reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $('#btn_value').val(data);
                $("#import_form").submit();
            }
        })
    }
</script>
@endpush
