@extends('email.reminders.master-notification-email')

@section('content')
    <p style="text-align:left; font-size:16px; font-weight: normal; color:#0B102A;" class="mobileFont">
        Your free-trail will be ending on {{ $expireDate }}. To continue your subscription, please click the button below and add your billing information.
    </p>
    <p style="text-align:left; font-size:16px; font-weight: normal; color:#0B102A;" class="mobileFont">
        If you have already added your billing information, you can disregard this email.
    </p>
@stop
