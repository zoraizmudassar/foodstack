@extends('layouts.admin.app')
@section('title', translate('messages.Admin_Landing_Page'))
@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <h1 class="page-header-title text-capitalize">
                    <div class="card-header-icon d-inline-flex mr-2 img">
                        <img src="{{ dynamicAsset('/public/assets/admin/img/landing-page.png') }}" class="mw-26px" alt="public">
                    </div>
                    <span>
                        {{ translate('Admin_Landing_Page') }}
                    </span>
                </h1>
                <div class="text--primary-2 py-1 d-flex flex-wrap align-items-center" type="button" data-toggle="modal" data-target="#how-it-works">
                    <strong class="mr-2">{{translate('See_how_it_works')}}</strong>
                    <div>
                        <i class="tio-info-outined"></i>
                    </div>
                </div>
            </div>
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                @include('admin-views.landing_page.top_menu.admin_landing_menu')
            </div>
        </div>
        @php($language=\App\Models\BusinessSetting::where('key','language')->first())
        @php($language = $language->value ?? null)
        @php($default_lang = str_replace('_', '-', app()->getLocale()))
        @if($language)
            <ul class="nav nav-tabs mb-4 border-0">
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
        @endif
                <div class="d-flex justify-content-between __gap-12px mb-3">
                    <h5 class="card-title d-flex align-items-center">
                        <span class="card-header-icon mr-2">
                            <img src="{{dynamicAsset('public/assets/admin/img/seller.png')}}" alt="" class="mw-100">
                        </span>
                        {{translate('Header_Section_Content')}}
                    </h5>
                </div>
                <div class="card">

                    <form action="{{ route('admin.landing_page.settings', 'about-us-data') }}" method="post" enctype="multipart/form-data">
                        @csrf
                    <div class="card-body">
                        <div class="lang_form default-form">
                            <input type="hidden" name="lang[]" value="default">

                            <div class="row g-4" >
                                <div class="col-sm-6">
                                    <label for="about_us_title" class="form-label">{{translate('Title')}}
                                        <span class="input-label-secondary text--title"  data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('Write_the_title_within_50_characters') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="about_us_title" type="text"  maxlength="50" name="about_us_title[]" value="{{ $header_title?->getRawOriginal('value') ?? null}}" class="form-control" placeholder="{{translate('messages.Enter_Title...')}}">
                                </div>
                                <div class="col-sm-6">
                                    <label for="about_us_sub_title" class="form-label">{{translate('Subtitle')}}
                                        <span class="input-label-secondary text--title"  data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('Write_the_subtitle_within_100_characters') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="about_us_sub_title" type="text" maxlength="100"  name="about_us_sub_title[]" value="{{ $header_sub_title?->getRawOriginal('value') ?? null}}" class="form-control" placeholder="{{translate('messages.Enter_Subtitle...')}}">
                                </div>
                                <div class="col-sm-12">


                                    <label for="about_us_text" class="form-label">{{translate('messages.Short_Description')}}
                                        <span class="input-label-secondary text--title"  data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('Write_the_short_description_within_160_characters') }}">
                                            <i class="tio-info-outined"></i>
                                        </span> </label>
                                        <textarea id="about_us_text"  maxlength="160" class="form-control" name="about_us_text[]" placeholder="{{translate('messages.Short_Description')}}">{{ $header_tag_line?->getRawOriginal('value') ?? null}}</textarea>
                                    </div>
                            </div>
                        </div>

                        @if ($language)

                        @foreach(json_decode($language) as $lang)
                        <?php
                            if($header_title?->translations){
                                    $header_title_translate = [];
                                    foreach($header_title?->translations as $t)
                                    {
                                        if($t->locale == $lang && $t->key=='about_us_title'){
                                            $header_title_translate[$lang]['value'] = $t->value;
                                        }
                                    }
                                }
                            if($header_sub_title?->translations){
                                    $header_sub_title_translate = [];
                                    foreach($header_sub_title?->translations as $t)
                                    {
                                        if($t->locale == $lang && $t->key=='about_us_sub_title'){
                                            $header_sub_title_translate[$lang]['value'] = $t->value;
                                        }
                                    }
                                }

                            if($header_tag_line?->translations){
                                    $header_tag_line_translate = [];
                                    foreach($header_tag_line?->translations as $t)
                                    {
                                        if($t->locale == $lang && $t->key=='about_us_text'){
                                            $header_tag_line_translate[$lang]['value'] = $t->value;
                                        }
                                    }
                                }
                            ?>

                                <div class="d-none lang_form" id="{{$lang}}-form">
                                    <input type="hidden" name="lang[]" value="{{$lang}}">
                                    <div class="row g-4" >
                                        <div class="col-sm-6">
                                            <label   for="about_us_title{{$lang}}" class="form-label">{{translate('Title')}} ({{strtoupper($lang)}})
                                                <span class="input-label-secondary text--title"  data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('Write_the_title_within_50_characters') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input id="about_us_title{{$lang}}"  type="text"  maxlength="50"  name="about_us_title[]" value="{{$header_title_translate[$lang]['value']??''}}" class="form-control" placeholder="{{translate('messages.Enter_Title...')}}">
                                        </div>
                                        <div class="col-sm-6">
                                            <label  for="about_us_sub_title{{$lang}}"  class="form-label">{{translate('Subtitle')}} ({{strtoupper($lang)}})
                                                <span class="input-label-secondary text--title"  data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('Write_the_subtitle_within_100_characters') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input  id="about_us_sub_title{{$lang}}" type="text"   maxlength="100" name="about_us_sub_title[]" value="{{$header_sub_title_translate[$lang]['value']??''}}" class="form-control" placeholder="{{translate('messages.Enter_Subtitle...')}}">
                                        </div>
                                        <div class="col-sm-12">
                                            <label   for="about_us_text{{$lang}}" class="form-label">{{translate('Short_description')}} ({{strtoupper($lang)}})
                                                <span class="input-label-secondary text--title"  data-toggle="tooltip" data-placement="right" data-original-title="{{ translate('Write_the_short_description_within_160_characters') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <textarea  id="about_us_text{{$lang}}" class="form-control" maxlength="160" name="about_us_text[]" placeholder="{{translate('messages.Short_Description')}}">{{$header_tag_line_translate[$lang]['value']??''}}</textarea>
                                        </div>
                                    </div>
                                </div>

                        @endforeach
                        @endif
                            <br>
                            <label class="form-label">
                            {{ translate('messages.Button_Content') }}
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="__bg-F8F9FC-card">
                                        <div class="lang_form default-form">
                                            <div class="form-group mb-md-0">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label for="about_us_app_button_name" class="form-label text-capitalize m-0">
                                                            {{translate('Button_Name')}}
                                                            <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('Write_the_button_name_within_15_characters')}}">
                                                                <i class="tio-info-outined"></i>
                                                            </span>
                                                        </label>
                                                </div>
                                                <input id="about_us_app_button_name" type="text" maxlength="15"  value="{{  $about_us_app_button_name?->getRawOriginal('value') ?? null }}" placeholder="{{translate('Ex:_Order_now')}}" class="form-control h--45px" name="about_us_app_button_name[]" >
                                            </div>
                                        </div>

                                        @if ($language)
                                            @foreach(json_decode($language) as $lang)
                                                <?php
                                                    if($about_us_app_button_name?->translations){
                                                            $about_us_app_button_name_translate = [];
                                                            foreach($about_us_app_button_name?->translations as $t)
                                                            {
                                                                if($t->locale == $lang && $t->key=='about_us_app_button_name'){
                                                                    $about_us_app_button_name_translate[$lang]['value'] = $t->value;
                                                                }
                                                            }
                                                        }

                                                    ?>


                                                <div class="d-none lang_form" id="{{$lang}}-form1">
                                                    <div class="form-group mb-md-0">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <label for="about_us_app_button_name{{$lang}}" class="form-label text-capitalize m-0">
                                                                    {{translate('Button_Name')}} ({{strtoupper($lang)}})
                                                                    <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('Write_the_button_name_within_15_characters')}}">
                                                                        <i class="tio-info-outined"></i>
                                                                    </span>
                                                                </label>
                                                        </div>
                                                        <input id="about_us_app_button_name{{$lang}}" type="text" maxlength="15"  value="{{$about_us_app_button_name_translate[$lang]['value']??''}}" placeholder="{{translate('Ex:_Order_now')}}" class="form-control h--45px" name="about_us_app_button_name[]" >
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group mb-md-0">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label text-capitalize m-0">
                                                    {{translate('Redirect_Link')}}
                                                    <span class="input-label-secondary text--title" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('Add_the_link/address_where_the_button_will_redirect.')}}">
                                                        <i class="tio-info-outined"></i>
                                                    </span>
                                                </label>
                                                    <label class="toggle-switch toggle-switch-sm m-0">
                                                        <input type="checkbox" {{$about_us_app_button_status == 1 ? 'checked': '' }}  id="about_us_app_button_status" value="1"
                                                               data-id="about_us_app_button_status"
                                                               data-type="toggle"
                                                               data-image-on="{{ dynamicAsset('/public/assets/admin/img/modal/mail-success.png') }}"
                                                               data-image-off="{{ dynamicAsset('/public/assets/admin/img/modal/mail-warning.png') }}"
                                                               data-title-on="<strong>{{translate('Want_to_enable_the_About_Us_button_here')}}</strong>"
                                                               data-title-off="<strong>{{translate('Want_to_disable_the_About_Us_button_here')}}</strong>"
                                                               data-text-on="<p>{{translate('If_enabled,_everyone_can_see_the_About_Us_button_on_the_landing_page')}}</p>"
                                                               data-text-off="<p>{{translate('If_disabled,_About_Us_button_will_be_hidden_from_the_landing_page')}}</p>"
                                                               class="status toggle-switch-input dynamic-checkbox-toggle"
                                                               name="about_us_app_button_status"
                                                        >
                                                        <span class="toggle-switch-label text mb-0">
                                                            <span class="toggle-switch-indicator"></span>
                                                        </span>
                                                    </label>
                                        </div>
                                            <input type="url"  placeholder="{{translate('Ex:_https://www.apple.com/app-store/')}}" class="form-control h--45px" name="redirect_link" value="{{  $about_us_button_content ?? null }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                <br>
                        <div class="d-flex gap-40px">
                            <div>
                                <label class="form-label d-block  mb-4">
                                    {{translate('Content_Image')}}  <span class="text--primary">({{translate('600x1700px')}}  )</span>
                                </label>
                                <div class="position-relative">
                                    <label class="upload-img-3 m-0 d-block">
                                        <div class="img">
                                            <img  src="{{ $about_us_content_image }}"
                                                  data-onerror-image="{{dynamicAsset('/public/assets/admin/img/upload-6.png')}}"
                                                  class="vertical-img max-w-187px onerror-image" alt="">
                                        </div>
                                        <input type="file" name="about_us_content_image" hidden="">
                                    </label>

                                    @if ($about_us_content_image)
                                    <span id="remove_image"
                                          class="remove_image_button remove-image"
                                          data-id="remove_image"
                                          data-title="{{translate('Warning!')}}"
                                          data-text="<p>{{translate('Are_you_sure_you_want_to_remove_this_image_?')}}</p>"
                                        > <i class="tio-clear"></i></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="btn--container justify-content-end mt-3">
                            <button type="reset" class="btn btn--reset">{{translate('Reset')}}</button>
                            <button type="submit"   class="btn btn--primary">{{translate('Save')}}</button>
                        </div>
                    </div>
                    </form>
                </div>
            </div>

                    <form  id="remove_image_form" action="{{ route('admin.remove_image') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" value="{{  $header_image_content?->id}}" >
                        <input type="hidden" name="model_name" value="DataSetting" >
                        <input type="hidden" name="image_path" value="about_us_image" >
                        <input type="hidden" name="field_name" value="value" >
                    </form>


@endsection
