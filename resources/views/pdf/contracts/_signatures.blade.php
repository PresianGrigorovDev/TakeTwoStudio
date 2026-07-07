<table style="width: 100%; margin-top: 50px; border: none; border-collapse: collapse;">
    <tr>
        {{-- Left: Client(s) --}}
        <td style="width: 50%; vertical-align: top; border: none; padding: 0 20px 0 0;">
            <strong>{{ ($data['contract_type'] ?? '') === 'wedding' && !empty($data['client2_name']) ? 'ВЪЗЛОЖИТЕЛИ' : 'ВЪЗЛОЖИТЕЛ' }}:</strong>

            <div style="margin-top: 8px;">
                {{ !empty($data['client1_name']) ? $data['client1_name'] : '' }}
                <div style="border-top: 1px solid #333; width: 250px; margin-top: 40px; padding-top: 5px; font-size: 10pt;">подпис</div>
            </div>

            @if(($data['contract_type'] ?? '') === 'wedding' && !empty($data['client2_name']))
            <div style="margin-top: 30px;">
                {{ $data['client2_name'] }}
                <div style="border-top: 1px solid #333; width: 250px; margin-top: 40px; padding-top: 5px; font-size: 10pt;">подпис</div>
            </div>
            @endif
        </td>

        {{-- Right: Executor(s) --}}
        <td style="width: 50%; vertical-align: top; border: none; padding: 0 0 0 20px;">
            <strong>{{ count($executors) > 1 ? 'ИЗПЪЛНИТЕЛИ' : 'ИЗПЪЛНИТЕЛ' }}:</strong>

            @foreach($executors as $executor)
            <div style="margin-top: 8px; {{ !$loop->first ? 'margin-top: 30px;' : '' }}">
                {{ $executor->name }}
                <div style="border-top: 1px solid #333; width: 250px; margin-top: 40px; padding-top: 5px; font-size: 10pt;">подпис</div>
            </div>
            @endforeach
        </td>
    </tr>
</table>
