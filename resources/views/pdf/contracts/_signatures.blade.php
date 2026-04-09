<div class="signatures">
    <strong>ВЪЗЛОЖИТЕЛ:</strong>
    <br><br>

    <div class="signature-block">
        {{ !empty($data['client1_name']) ? $data['client1_name'] : '' }}
        <div class="signature-line">подпис</div>
    </div>

    @if(($data['contract_type'] ?? '') === 'wedding')
    <div class="signature-block">
        {{ !empty($data['client2_name']) ? $data['client2_name'] : '' }}
        <div class="signature-line">подпис</div>
    </div>
    @endif

    <br>
    <strong>{{ count($executors) > 1 ? 'ИЗПЪЛНИТЕЛИ' : 'ИЗПЪЛНИТЕЛ' }}:</strong>
    <br><br>

    @foreach($executors as $executor)
    <div class="signature-block">
        {{ $executor->name }}
        <div class="signature-line">подпис</div>
    </div>
    @endforeach
</div>
