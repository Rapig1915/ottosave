<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Content-Type" content="text/html charset=UTF-8" />
        <title></title>
        <style>
            @media screen and (max-width:500px) {
                .grayBackground {
                    padding-top: 0px !important;
                    padding-bottom: 0px !important;
                }
                .logoWrapper {
                    margin-bottom: 50px !important;
                    padding-top: 50px !important;
                }
                .mobileFont {
                    font-size: 20px !important;
                }
                .mobileButton {
                    padding: 20px 75px 20px 75px !important;
                    font-size: 18px !important;
                }
            }
        </style>
    </head>
    <body style="margin: 0;">
        <div style="font-family:sans-serif; background-color:#F7F7F7; padding-top:50px; padding-bottom:50px; color:#0B102A;" class="grayBackground">
            <div style="width:100%; max-width:500px; margin:auto; padding:0px 10px 100px 10px; background-color:#FFFFFF;">
                <div style="width:100%; max-width:400px; margin: auto;">
                    <div style="margin-bottom:100px; padding-top: 100px; width:200px; margin-left:auto; margin-right:auto;" class="logoWrapper">
                        <img src="{{ config('app.url') }}/images/logo.png" style="margin:auto;display:block;width:200px;" alt="logo">
                    </div>

                    <p style="font-size:15px; font-weight: normal; margin-bottom:32px; color:#0B102A;" class="mobileFont">
                        Hi {{ $accountInvite->name }}!
                        <br/>
                        <br/>
                        You've been invited to access the following account:
                        <br/>
                        <center><b>{{ $inviterName }}</b></center>
                    </p>

                    <div style="width:100%; text-align:center; margin-top: 75px;">
                        <a href="{{ $inviteLink }}" target="_blank" style="color:#ffffff; text-decoration:none;">
                            <div style="margin:auto; text-align:center; background-color:#3E32C4; display:table-cell; vertical-align:middle; font-weight:500; font-size:14px; border-radius:6px; padding:13px 55px 13px 55px;" class="mobileButton">
                                Accept Invite
                            </div>
                        </a>
                    </div>

                    <div style="max-width: 500px; margin-top: 75px; font-size: 14px; text-align: center; border-bottom:2px solid #EBEBEB">
                    </div>

                    <div style="padding-left: 10px; padding-right: 10px; margin-top: 50px;">
                        <div style="max-width: 600px; margin: auto;">
                            @include('email.parts.support')
                            @include('email.parts.copyright')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
