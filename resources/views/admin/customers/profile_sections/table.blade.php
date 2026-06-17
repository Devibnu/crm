@if ($rows->isEmpty())
    <div class="customer-profile-empty">
        <strong>{{ $empty }}</strong>
        <p>Data akan tampil otomatis setelah aktivitas customer tercatat.</p>
    </div>
@else
    <div class="customer-table-wrap">
        <table class="customer-table customer-profile-table">
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
