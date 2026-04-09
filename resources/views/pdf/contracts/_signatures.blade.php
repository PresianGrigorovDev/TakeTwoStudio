<div class="signatures">
    <table>
        <tr>
            <td>
                <strong>ВЪЗЛОЖИТЕЛ:</strong>
                <br><br>
                {{ $data['client1_name'] ?? '' }}
                <div class="signature-line">подпис</div>

                @if(($data['contract_type'] ?? '') === 'wedding' && !empty($data['client2_name']))
                    <br><br>
                    {{ $data['client2_name'] }}
                    <div class="signature-line">п��дпис</div>
                @endif
            </td>
            <td>
                <strong>ИЗПЪЛНИТЕЛ:</strong>
                <br><br>
                @foreach($executors as $executor)
                    {{ $executor->name }}
                    <div class="signature-line">подпис</div>
                    @if(!$loop->last)<br>@endif
                @endforeach
            </td>
        </tr>
    </table>
</div>
