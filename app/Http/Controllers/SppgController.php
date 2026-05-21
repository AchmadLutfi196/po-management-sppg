<?php

namespace App\Http\Controllers;

use App\Models\Sppg;
use App\Traits\ProcurementHelpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SppgController extends Controller
{
    use ProcurementHelpers;

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        if ($request->has('clear')) {
            $request->session()->forget('sppg_filters');

            return redirect()->route('master-sppg.index');
        }

        $hasFilterParams = $request->has('search') || $request->has('page');

        if ($hasFilterParams) {
            $filters = [
                'search' => $request->string('search')->toString(),
                'page' => $request->string('page')->toString(),
            ];
            $request->session()->put('sppg_filters', $filters);
        } else {
            if ($request->session()->has('sppg_filters')) {
                $queryParams = array_merge(
                    $request->session()->get('sppg_filters'),
                    $request->only(['edit', 'mode'])
                );

                return redirect()->route('master-sppg.index', $queryParams);
            }
        }

        $sppgs = Sppg::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): Builder {
                $search = strtolower($request->string('search')->toString());

                return $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(location) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(pic_name) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sppg $sppg): array => $this->sppgToArray($sppg));

        return view('master-sppg.index', [
            'currentUser' => $this->currentUser(),
            'sppgs' => $sppgs,
            'filters' => ['search' => $request->string('search')->toString()],
            'editSppg' => $request->filled('edit') ? $this->sppgToArray(Sppg::query()->findOrFail($request->string('edit')->toString())) : null,
            'isCreating' => $request->string('mode')->toString() === 'create',
        ]);
    }

    public function create(): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        return redirect()->route('master-sppg.index', ['mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        $validated = $this->validateSppg($request);
        Sppg::query()->create($validated);

        return redirect()->route('master-sppg.index')->with('success', 'SPPG berhasil ditambahkan.');
    }

    public function edit(string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        return redirect()->route('master-sppg.index', ['edit' => $id]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        $sppg = Sppg::query()->findOrFail($id);
        $sppg->update($this->validateSppg($request, $sppg));

        return redirect()->route('master-sppg.index')->with('success', 'SPPG berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $this->authorizeAdmin();

        $sppg = Sppg::query()->findOrFail($id);

        if ($sppg->purchaseOrders()->exists()) {
            return redirect()
                ->route('master-sppg.index')
                ->withErrors(['sppg' => 'SPPG tidak bisa dihapus karena sudah digunakan pada PO.']);
        }

        $sppg->delete();

        return redirect()->route('master-sppg.index')->with('success', 'SPPG berhasil dihapus.');
    }

    /**
     * @return array{code: string, name: string, location: ?string, pic_name: ?string, whatsapp: ?string}
     */
    private function validateSppg(Request $request, ?Sppg $sppg = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('sppgs', 'code')->ignore($sppg)],
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'pic_name' => ['nullable', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        return $validated;
    }

    /**
     * @return array{id: string, code: string, name: string, location: ?string, pic_name: ?string, whatsapp: ?string}
     */
    private function sppgToArray(Sppg $sppg): array
    {
        return [
            'id' => (string) $sppg->id,
            'code' => $sppg->code,
            'name' => $sppg->name,
            'location' => $sppg->location,
            'pic_name' => $sppg->pic_name,
            'whatsapp' => $sppg->whatsapp,
        ];
    }
}
