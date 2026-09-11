<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGradingScaleRequest;
use App\Models\GradingScale;
use App\Models\TaskGrade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GradingScaleController extends Controller
{
    public function update(UpdateGradingScaleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $scale = GradingScale::query()->lockForUpdate()->findOrFail(1);
            $data = $request->validated();
            $minimum = (float) $data['minimum'];
            $maximum = (float) $data['maximum'];

            if ($minimum !== $scale->minimum || $maximum !== $scale->maximum) {
                TaskGrade::query()->chunkById(200, function ($grades) use ($scale, $minimum, $maximum): void {
                    foreach ($grades as $grade) {
                        $grade->update(['score' => round($minimum + ($maximum - $minimum) * $scale->toPercentage((float) $grade->score) / 100, 2)]);
                    }
                });
            }
            $scale->update($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Escala de notas actualizada para toda la institución.']);

        return back();
    }
}
