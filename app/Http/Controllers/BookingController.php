<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAvailableSlotsRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Services\BookingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * BookingController
 * Контроллер для обработки запросов, связанных с бронированием услуг.
 */
class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected ServiceRepository $serviceRepository;

    public function __construct(BookingService $bookingService, ServiceRepository $serviceRepository)
    {
        $this->bookingService = $bookingService;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Отображает главную страницу со списком всех доступных услуг.
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Index', [
            'services' => $this->serviceRepository->all(['id', 'name', 'duration_minutes']),
        ]);
    }

    /**
     * Отображает страницу с деталями услуги и формой бронирования.
     * @param  Request  $request
     * @param  Service  $service
     * @return Response
     */
    public function show(Request $request, Service $service): Response
    {
        $service->load('schedules');

        $availableDays = $service->schedules->pluck('day_of_week')->unique()->values()->all();

        return Inertia::render('Booking/Show', [
            'service' => $service,
            'availableDays' => $availableDays,
        ]);
    }

    /**
     * Возвращает доступные временные слоты для бронирования на указанную дату и услугу.
     * @param  GetAvailableSlotsRequest  $request
     * @param  Service  $service
     * @return JsonResponse
     */
    public function getAvailableSlots(GetAvailableSlotsRequest $request, Service $service): JsonResponse
    {
        $validated = $request->validated();

        $date = Carbon::parse($validated['date']);

        $availableSlots = $this->bookingService->getAvailableSlots($service, $date);

        return response()->json($availableSlots);
    }

    /**
     * Сохраняет новое бронирование в базе данных.
     * @param  StoreBookingRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->bookingService->createBooking($validated);

            return back()->with('success', 'Ваша запись была успешно создана!');
        } catch (Exception $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }
}
