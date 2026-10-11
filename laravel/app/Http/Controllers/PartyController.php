<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Shared list/add/edit/merge/delete for customers and suppliers.
 *
 * @template TParty of Customer|Supplier
 */
abstract class PartyController extends Controller
{
    /**
     * @return class-string<TParty>
     */
    abstract protected function model(): string;

    /**
     * "customer" or "supplier": used for route names, views and labels.
     */
    abstract protected function kind(): string;

    /**
     * Add the money columns (billed, paid, balance, document count) shown on the list.
     *
     * @param  Builder<TParty>  $query
     * @return Builder<TParty>
     */
    abstract protected function withTotals(Builder $query): Builder;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort') === 'balance' ? 'balance' : 'name';

        $parties = $this->withTotals($this->model()::query())
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('contact_person', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($sort === 'balance', fn (Builder $q) => $q->orderByDesc('balance'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('parties.index', [
            'kind' => $this->kind(),
            'parties' => $parties,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function create(): View
    {
        return view('parties.form', ['kind' => $this->kind(), 'party' => new ($this->model())]);
    }

    public function store(Request $request): RedirectResponse
    {
        $party = $this->model()::create($this->validated($request));

        return redirect()->route($this->kind().'s.show', $party)->with('success', ucfirst($this->kind()).' added.');
    }

    public function editParty(Customer|Supplier $party): View
    {
        return view('parties.form', ['kind' => $this->kind(), 'party' => $party]);
    }

    /**
     * Renaming also updates the name printed on the party's documents, so lists and AR stay grouped.
     */
    public function updateParty(Request $request, Customer|Supplier $party): RedirectResponse
    {
        $data = $this->validated($request, $party);

        DB::transaction(function () use ($party, $data) {
            $renamed = $data['name'] !== $party->name;
            $party->update($data);

            if ($renamed) {
                foreach ($party::DOCUMENTS as $relation => $nameColumn) {
                    $party->{$relation}()->toBase()->update([$nameColumn => $party->name]);
                }
            }
        });

        return redirect()->route($this->kind().'s.show', $party)->with('info', ucfirst($this->kind()).' updated.');
    }

    /**
     * Move every document of $party onto $target (e.g. "Acme" and "ACME Ltd" are the same customer), then delete $party.
     */
    public function mergeParty(Request $request, Customer|Supplier $party): RedirectResponse
    {
        $data = $request->validate([
            'target_id' => ['required', 'integer', Rule::exists($party->getTable(), 'id'), Rule::notIn([$party->id])],
        ], [
            'target_id.not_in' => 'Choose a different '.$this->kind().' to merge into.',
        ]);
        $target = $this->model()::findOrFail($data['target_id']);

        DB::transaction(function () use ($party, $target) {
            foreach ($party::DOCUMENTS as $relation => $nameColumn) {
                $party->{$relation}()->toBase()->update([
                    $party->{$relation}()->getForeignKeyName() => $target->id,
                    $nameColumn => $target->name,
                ]);
            }

            // Keep contact details the target is missing
            $target->fill(array_filter(
                $party->only(['contact_person', 'phone', 'email', 'address', 'notes']),
                fn ($value, string $field) => filled($value) && blank($target->{$field}),
                ARRAY_FILTER_USE_BOTH,
            ))->save();

            $party->delete();
        });

        return redirect()->route($this->kind().'s.show', $target)
            ->with('success', "Merged “{$party->name}” into “{$target->name}”.");
    }

    /**
     * Only parties without documents can be deleted; otherwise merge them.
     */
    public function destroyParty(Customer|Supplier $party): RedirectResponse
    {
        $documentCount = collect(array_keys($party::DOCUMENTS))->sum(fn (string $relation) => $party->{$relation}()->count());

        if ($documentCount > 0) {
            return back()->withErrors([
                'party' => "“{$party->name}” has {$documentCount} linked documents. Merge it into another {$this->kind()} instead.",
            ]);
        }

        $party->delete();

        return redirect()->route($this->kind().'s.index')->with('warning', ucfirst($this->kind()).' deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Customer|Supplier|null $party = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique($this->model(), 'name')->ignore($party?->id)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ], [
            'name.unique' => 'A '.$this->kind().' with this name already exists.',
        ]);
        $data['name'] = trim($data['name']);

        return $data;
    }
}
