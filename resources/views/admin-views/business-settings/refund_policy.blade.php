@extends('layouts.admin.app')

@section('title',translate('messages.refund_policy'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header_ pb-4">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">{{translate('messages.refund_policy')}}</h1>
                    <h5 class="d-flex flex-wrap justify-content-end">
                        <label class="switch--custom-label toggle-switch toggle-switch-sm d-inline-flex">
                            <span class="mr-2 switch--custom-label-text text-primary on text-uppercase">{{ translate('messages.on') }}</span>
                            <span class="mr-2 switch--custom-label-text off text-uppercase">{{ translate('messages.Status') }}</span>
                            <input type="checkbox" id="data_status"   class="toggle-switch-input"
                            {{$refund_policy_status?->value == 1?'checked':''}}
                            >
                            <span class="toggle-switch-label text">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                        </label>
                    </h5>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.business-settings.refund-policy')}}" method="post" id="tnc-form">
                    @csrf

                    @php($language=\App\Models\BusinessSetting::where('key','language')->first())
                    @php($language = $language->value ?? null)
                    @php($default_lang = str_replace('_', '-', app()->getLocale()))
                    @if ($language)
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

                    <div class="form-group lang_form" id="default-form">
                        <input type="hidden" name="lang[]" value="default">
                        <textarea class="ckeditor form-control" name="refund_policy[]">{!! $refund_policy?->getRawOriginal('value') ?? '' !!}</textarea>
                    </div>

                    @if ($language)
                        @forelse(json_decode($language) as $lang)
                            <?php
                                if($refund_policy?->translations){
                                    $translate = [];
                                    foreach($refund_policy?->translations as $t)
                                    {
                                        if($t->locale == $lang && $t->key=="refund_policy"){
                                            $translate[$lang]['refund_policy'] = $t->value;
                                        }
                                    }
                                }
                                ?>
                            <div class="form-group d-none lang_form" id="{{$lang}}-form">
                                <textarea class="ckeditor form-control" name="refund_policy[]">{!!  $translate[$lang]['refund_policy'] ?? null !!}</textarea>
                            </div>
                            <input type="hidden" name="lang[]" value="{{$lang}}">
                            @empty
                        @endforelse
                    @endif

                    <div class="btn--container justify-content-end">
                        <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
<script src="{{dynamicAsset('public/assets/admin/ckeditor/ckeditor.js')}}"></script>
<script type="text/javascript">
        $(document).ready(function () {
                $('body').on('change','#data_status', function(){
                    let status;
                if(this.checked){
                    status = 1;
                }else{
                    status = 0;
                }
            let url= '{{ url('admin/business-settings/pages/refund-policy') }}/'+status;
            $.ajax({
                url: url,
                method: 'get',
                success: function() {
                    toastr.success('{{ translate('messages.status_updated!') }}', {
                    CloseButton: true,
                    ProgressBar: true
                    });
                }
            });

            });
        });
</script>

@endpush
