<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <div>
            <h3>Finicity Institution Error</h3>
            <p>
                A user on {{ config('app.name') }} has an institution experiencing errors communicating with Finicity which cannot be fixed manually by the user. A support ticket will need to be opened with Finicity for the account details below.
            </p>
            <table style="border-collapse: collapse; margin-bottom:10px;">
                <tr>
                    <td style="border: 1px solid black; padding:5px;">Account Id:</td>
                    <td style="border: 1px solid black; padding:5px;">{{ $institution->account->id }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid black; padding:5px; font-weight:bold;" colspan="2">Affected User details:</td>
                </tr>
                @foreach ($institution->account->users as $user)
                    <tr>
                        <td style="border: 1px solid black; padding:5px;">User Id: {{ $user->id }}</td>
                        <td style="border: 1px solid black; padding:5px;">User Name: {{ $user->name }}</td>
                        <td style="border: 1px solid black; padding:5px;">User Email: {{ $user->email }}</td>
                    </tr>
                @endforeach
            </table>
            @if(!empty($finicityAccount))
                <table style="border-collapse: collapse; margin-bottom:10px;">
                    <tr>
                        <td style="border: 1px solid black; padding:5px; font-weight:bold;" colspan="2">Error Details:</td>
                    </tr>
                    @foreach ($finicityAccount as $key => $value)
                        <tr>
                            <td style="border: 1px solid black; padding:5px;">{{ $key }}</td>
                            <td style="border: 1px solid black; padding:5px;">{{ json_encode($value) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </body>
</html>
