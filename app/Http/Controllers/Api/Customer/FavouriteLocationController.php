<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\FavouriteLocation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FavouriteLocationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        return $this->success(
            $request->user()->favouriteLocations()->latest()->get()->map(fn ($l) => $this->format($l)),
            'Locations fetched.'
        );
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['user_id'] = $request->user()->id;
        $location = FavouriteLocation::create($data);

        return $this->success($this->format($location), 'Location saved.', 201);
    }

    public function update(Request $request, string $id)
    {
        $location = $request->user()->favouriteLocations()->where('id', $id)->first();
        if (! $location) {
            return $this->error('Location not found.', 404);
        }

        $location->update($this->validateData($request));

        return $this->success($this->format($location->fresh()), 'Location updated.');
    }

    public function destroy(Request $request, string $id)
    {
        $location = $request->user()->favouriteLocations()->where('id', $id)->first();
        if (! $location) {
            return $this->error('Location not found.', 404);
        }
        $location->delete();

        return $this->success(null, 'Location deleted.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'in:home,office,other'],
            'custom_label' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);
    }

    private function format(FavouriteLocation $l): array
    {
        return [
            'id' => $l->id,
            'label' => $l->label,
            'custom_label' => $l->custom_label,
            'address' => $l->address,
            'lat' => (float) $l->lat,
            'lng' => (float) $l->lng,
        ];
    }
}
