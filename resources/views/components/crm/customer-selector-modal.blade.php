@props([
    'modalId',
    'title' => 'Select Customer',
    'description' => 'Select a customer before continuing.',
    'customers' => collect(),
    'routeName',
    'continueLabel' => 'Continue',
    'cancelLabel' => 'Cancel',
    'emptyMessage' => 'No customers available.',
])

@php
    $customers = collect($customers);
    $titleId = $modalId.'Title';
    $descriptionId = $modalId.'Description';
    $searchId = $modalId.'Search';
@endphp

<div
    id="{{ $modalId }}"
    class="crm-customer-selector-modal"
    data-customer-selector-modal="{{ $modalId }}"
    hidden
>
    <div class="crm-customer-selector-dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}" aria-describedby="{{ $descriptionId }}">
        <header class="crm-customer-selector-header">
            <div>
                <h2 id="{{ $titleId }}">{{ $title }}</h2>
                <p id="{{ $descriptionId }}">{{ $description }}</p>
            </div>
            <button type="button" class="crm-customer-selector-close" data-customer-selector-close aria-label="Close customer selector">×</button>
        </header>

        <div class="crm-customer-selector-search">
            <label for="{{ $searchId }}">Search Customer</label>
            <input
                id="{{ $searchId }}"
                type="search"
                placeholder="Search customer..."
                data-customer-selector-search
                autocomplete="off"
            >
        </div>

        <div class="crm-customer-selector-list" data-customer-selector-list>
            @forelse ($customers as $customer)
                @php
                    $contact = $customer->email ?: $customer->phone;
                    $searchText = trim($customer->name.' '.$customer->company_name.' '.$customer->email.' '.$customer->phone);
                @endphp
                <label class="crm-customer-selector-option" data-customer-selector-option data-customer-search="{{ \Illuminate\Support\Str::lower($searchText) }}">
                    <input
                        type="radio"
                        name="{{ $modalId }}_customer"
                        value="{{ $customer->id }}"
                        data-customer-selector-choice
                        data-url="{{ route($routeName, ['customer' => $customer]) }}"
                    >
                    <span>
                        <strong>{{ $customer->name }}</strong>
                        <small>{{ $customer->company_name ?: 'No company' }}</small>
                        <em>{{ $contact ?: 'No email or phone' }}</em>
                    </span>
                </label>
            @empty
                <div class="crm-customer-selector-empty">{{ $emptyMessage }}</div>
            @endforelse

            @if ($customers->isNotEmpty())
                <div class="crm-customer-selector-empty" data-customer-selector-no-results hidden>No customers match your search.</div>
            @endif
        </div>

        <footer class="crm-customer-selector-footer">
            <button type="button" class="btn btn-muted" data-customer-selector-close>{{ $cancelLabel }}</button>
            <button type="button" class="btn btn-primary" data-customer-selector-continue disabled>{{ $continueLabel }}</button>
        </footer>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const closeModal = (modal) => {
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
            };

            const openModal = (modal) => {
                modal.hidden = false;
                modal.removeAttribute('aria-hidden');
                const searchInput = modal.querySelector('[data-customer-selector-search]');
                if (searchInput) searchInput.focus();
            };

            document.querySelectorAll('[data-customer-selector-modal]').forEach((modal) => {
                const modalId = modal.dataset.customerSelectorModal;
                const continueButton = modal.querySelector('[data-customer-selector-continue]');
                const searchInput = modal.querySelector('[data-customer-selector-search]');
                const options = Array.from(modal.querySelectorAll('[data-customer-selector-option]'));
                const noResults = modal.querySelector('[data-customer-selector-no-results]');

                modal.setAttribute('aria-hidden', 'true');

                document.querySelectorAll(`[data-customer-selector-trigger="${modalId}"]`).forEach((trigger) => {
                    trigger.addEventListener('click', () => openModal(modal));
                });

                modal.querySelectorAll('[data-customer-selector-close]').forEach((button) => {
                    button.addEventListener('click', () => closeModal(modal));
                });

                modal.querySelectorAll('[data-customer-selector-choice]').forEach((choice) => {
                    choice.addEventListener('change', () => {
                        if (continueButton) continueButton.disabled = false;
                    });
                });

                if (continueButton) {
                    continueButton.addEventListener('click', () => {
                        const selected = modal.querySelector('[data-customer-selector-choice]:checked');
                        if (selected?.dataset.url) window.location.href = selected.dataset.url;
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('input', () => {
                        const query = searchInput.value.trim().toLowerCase();
                        let visibleCount = 0;

                        options.forEach((option) => {
                            const visible = option.dataset.customerSearch.includes(query);
                            option.hidden = ! visible;
                            if (visible) visibleCount += 1;
                        });

                        if (noResults) noResults.hidden = visibleCount !== 0;
                    });
                }

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal(modal);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && ! modal.hidden) closeModal(modal);
                });
            });
        });
    </script>
@endonce
