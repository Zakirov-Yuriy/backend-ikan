<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* Заявки с сайта: список, отметка «обработана», удаление */
class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:new,done']]);
        $query = Lead::query()->latest('created_at');
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return view('admin.leads.index', [
            'leads' => $query->paginate(30)->withQueryString(),
            'filters' => $filters,
            'newCount' => Lead::query()->fresh()->count(),
        ]);
    }

    /* Переключить: новая или обработана */
    public function toggle(Lead $lead): RedirectResponse
    {
        $lead->update(['status' => $lead->isNew() ? Lead::STATUS_DONE : Lead::STATUS_NEW]);

        return back()->with('success', $lead->isNew() ? 'Заявка снова отмечена как новая.' : 'Заявка отмечена как обработанная.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Заявка удалена.');
    }
}
