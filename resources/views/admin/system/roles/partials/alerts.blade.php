@if (session('success'))
    <div class="card customer-alert success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="card customer-alert danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="card customer-alert danger" data-lang-en="Please review the role and permission input." data-lang-id="Periksa kembali input role dan permission.">Periksa kembali input role dan permission.</div>
@endif
