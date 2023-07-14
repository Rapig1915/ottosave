<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
        <div>
            <h3>User List Updated</h3>
            @if(!empty($eventType))
                <h4>Update type: {{ $eventType }}</h4>
                @if($eventType === 'New User Added')
                    <h4>Subscription Origin: {{ $account->subscription_origin }}</h4>
                @endif
            @endif
            @if(!empty($oldValue))
                <p>Old Value: {{ $oldValue }}</p>
            @endif
            @if(!empty($newValue))
                <p>New Value: {{ $newValue }}</p>
            @endif
            <h4>Affected account id: {{ $account->id }}</h4>
            <table style="border-collapse: collapse; margin-bottom:10px;">
                <caption>Affected Users:</caption>
                <tr>
                    <th style="border: 1px solid black; padding:5px;">User ID</th>
                    <th style="border: 1px solid black; padding:5px;">User Name</th>
                    <th style="border: 1px solid black; padding:5px;">User Email</th>
                </tr>
                @foreach ($users as $user)
                    <tr>
                        <td style="border: 1px solid black; padding:5px;">{{ $user->id }}</td>
                        <td style="border: 1px solid black; padding:5px;">{{ $user->name  }}</td>
                        <td style="border: 1px solid black; padding:5px;">{{ $user->email  }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </body>
</html>
