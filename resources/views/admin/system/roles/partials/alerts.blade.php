@if (session('success'))
    <div class="card customer-alert success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="card customer-alert danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="card customer-alert danger">Periksa kembali input role dan permission.</div>
@endif
