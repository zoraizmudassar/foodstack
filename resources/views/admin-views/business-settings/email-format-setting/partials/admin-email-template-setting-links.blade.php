<div class="d-flex flex-wrap justify-content-between align-items-center mb-5 mt-4 __gap-12px">
    <div class="js-nav-scroller hs-nav-scroller-horizontal mt-2">
        <!-- Nav -->
        <ul class="nav nav-tabs border-0 nav--tabs nav--pills">
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/forgot-password') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','forgot-password']) }}">
                    {{translate('Forgot_Password')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/restaurant-registration') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','restaurant-registration']) }}">
                    {{translate('New_Restaurant_Registration')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/dm-registration') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','dm-registration']) }}">
                    {{translate('New_Deliveryman_Registration')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/withdraw-request') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','withdraw-request']) }}">
                    {{translate('Withdraw_Request')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/campaign-request') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','campaign-request']) }}">
                    {{translate('Campaign_Join_Request')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/refund-request') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','refund-request']) }}">
                    {{translate('Refund_Request')}}
                </a>
            </li>
            {{-- <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/login') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','login']) }}">
                    {{translate('Login mail')}}
                </a>
            </li> --}}
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/new-advertisement') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','new-advertisement']) }}">
                    {{translate('New_Advertisement_Request')}}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('admin/business-settings/email-setup/admin/update-advertisement') ? 'active' : '' }}"
                href="{{ route('admin.business-settings.email-setup', ['admin','update-advertisement']) }}">
                    {{translate('Advertisement_Update_Request')}}
                </a>
            </li>
        </ul>
        <!-- End Nav -->
    </div>
</div>
