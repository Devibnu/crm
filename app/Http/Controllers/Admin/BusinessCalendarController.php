<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBusinessCalendarRequest;
use App\Http\Requests\Admin\StoreBusinessCalendarHolidayRequest;
use App\Http\Requests\Admin\UpdateBusinessCalendarHolidayRequest;
use App\Http\Requests\Admin\UpdateBusinessCalendarRequest;
use App\Models\BusinessCalendar;
use App\Models\BusinessCalendarHoliday;
use App\Services\BusinessCalendar\BusinessCalendarService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessCalendarController extends Controller
{
    public function __construct(
        protected BusinessCalendarService $businessCalendarService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $active = trim((string) $request->query('is_active', ''));
        $default = trim((string) $request->query('is_default', ''));

        $calendars = BusinessCalendar::query()
            ->with('workingHours')
            ->when($search !== '', fn ($query) => $query->where(function ($innerQuery) use ($search) {
                $innerQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('timezone', 'like', "%{$search}%");
            }))
            ->when(in_array($active, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $active === 'active'))
            ->when(in_array($default, ['yes', 'no'], true), fn ($query) => $query->where('is_default', $default === 'yes'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => BusinessCalendar::query()->count(),
            'active' => BusinessCalendar::query()->where('is_active', true)->count(),
            'default' => BusinessCalendar::query()->defaultCalendar()->value('name'),
            'upcoming_holidays' => BusinessCalendarHoliday::query()->upcoming()->count(),
        ];

        return view('admin.service.business-calendars.index', [
            'calendars' => $calendars,
            'search' => $search,
            'selectedActive' => $active,
            'selectedDefault' => $default,
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.business-calendars.create', [
            'calendar' => null,
            'timezoneOptions' => $this->timezoneOptions(),
            'dayLabels' => BusinessCalendar::ISO_DAYS,
        ]);
    }

    public function store(CreateBusinessCalendarRequest $request): RedirectResponse
    {
        $calendar = $this->businessCalendarService->create($request->calendarData(), $request->workingHoursData());

        return redirect()
            ->route('admin.service.business-calendars.show', $calendar)
            ->with('success', 'Business calendar berhasil ditambahkan.');
    }

    public function show(BusinessCalendar $businessCalendar): View
    {
        return view('admin.service.business-calendars.show', [
            'calendar' => $businessCalendar->load(['workingHours', 'holidays']),
            'dayLabels' => BusinessCalendar::ISO_DAYS,
        ]);
    }

    public function edit(BusinessCalendar $businessCalendar): View
    {
        return view('admin.service.business-calendars.edit', [
            'calendar' => $businessCalendar->load('workingHours'),
            'timezoneOptions' => $this->timezoneOptions(),
            'dayLabels' => BusinessCalendar::ISO_DAYS,
        ]);
    }

    public function update(UpdateBusinessCalendarRequest $request, BusinessCalendar $businessCalendar): RedirectResponse
    {
        $this->businessCalendarService->update($businessCalendar, $request->calendarData(), $request->workingHoursData());

        return redirect()
            ->route('admin.service.business-calendars.show', $businessCalendar)
            ->with('success', 'Business calendar berhasil diperbarui.');
    }

    public function destroy(BusinessCalendar $businessCalendar): RedirectResponse
    {
        $this->businessCalendarService->delete($businessCalendar);

        return redirect()
            ->route('admin.service.business-calendars.index')
            ->with('success', 'Business calendar berhasil dihapus.');
    }

    public function setDefault(BusinessCalendar $businessCalendar): RedirectResponse
    {
        $this->businessCalendarService->setDefault($businessCalendar);

        return redirect()
            ->route('admin.service.business-calendars.show', $businessCalendar)
            ->with('success', 'Default business calendar berhasil diperbarui.');
    }

    public function storeHoliday(StoreBusinessCalendarHolidayRequest $request, BusinessCalendar $businessCalendar): RedirectResponse
    {
        $this->businessCalendarService->addHoliday($businessCalendar, $request->holidayData());

        return redirect()
            ->route('admin.service.business-calendars.show', $businessCalendar)
            ->with('success', 'Holiday berhasil ditambahkan.');
    }

    public function updateHoliday(UpdateBusinessCalendarHolidayRequest $request, BusinessCalendar $businessCalendar, BusinessCalendarHoliday $holiday): RedirectResponse
    {
        $this->businessCalendarService->updateHoliday($businessCalendar, $holiday, $request->holidayData());

        return redirect()
            ->route('admin.service.business-calendars.show', $businessCalendar)
            ->with('success', 'Holiday berhasil diperbarui.');
    }

    public function destroyHoliday(BusinessCalendar $businessCalendar, BusinessCalendarHoliday $holiday): RedirectResponse
    {
        $this->businessCalendarService->deleteHoliday($businessCalendar, $holiday);

        return redirect()
            ->route('admin.service.business-calendars.show', $businessCalendar)
            ->with('success', 'Holiday berhasil dihapus.');
    }

    /**
     * @return array<int, string>
     */
    protected function timezoneOptions(): array
    {
        return collect(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC'])
            ->merge(DateTimeZone::listIdentifiers())
            ->unique()
            ->values()
            ->all();
    }
}
