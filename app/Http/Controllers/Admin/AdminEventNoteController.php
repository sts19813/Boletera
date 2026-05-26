<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEventNote;
use App\Models\AdminEventNoteAttachment;
use App\Models\AdminEventNoteHistory;
use App\Models\Eventos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminEventNoteController extends Controller
{
    public function index()
    {
        $events = Eventos::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $notes = AdminEventNote::query()
            ->with([
                'event:id,name',
                'creator:id,name',
                'attachments:id,admin_event_note_id,original_name,storage_path,mime_type,size_bytes',
                'histories.changedByUser:id,name',
            ])
            ->latest()
            ->get();

        return view('admin.event_notes.index', [
            'events' => $events,
            'notes' => $notes,
            'categories' => AdminEventNote::categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'uuid', 'exists:eventos,id'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(AdminEventNote::categories()))],
            'title' => ['required', 'string', 'max:150'],
            'note' => ['required', 'string', 'max:5000'],
            'counterparty' => ['nullable', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv,txt'],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $note = AdminEventNote::create([
                'event_id' => $validated['event_id'],
                'category' => $validated['category'],
                'title' => $validated['title'],
                'note' => $validated['note'],
                'counterparty' => $validated['counterparty'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'created_by' => (int) $request->user()->id,
            ]);

            $this->storeAttachments($note, $request->file('attachments', []));
        });

        return redirect()
            ->route('admin.event-notes.index')
            ->with('success', 'Nota administrativa guardada correctamente.');
    }

    public function update(Request $request, AdminEventNote $eventNote): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'uuid', 'exists:eventos,id'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(AdminEventNote::categories()))],
            'title' => ['required', 'string', 'max:150'],
            'note' => ['required', 'string', 'max:5000'],
            'counterparty' => ['nullable', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,csv,txt'],
            '_edit_note_id' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($validated, $request, $eventNote): void {
            $trackedFields = ['event_id', 'category', 'title', 'note', 'counterparty', 'amount'];

            $before = [];
            foreach ($trackedFields as $field) {
                $before[$field] = $eventNote->{$field};
            }

            $newValues = [
                'event_id' => $validated['event_id'],
                'category' => $validated['category'],
                'title' => $validated['title'],
                'note' => $validated['note'],
                'counterparty' => $validated['counterparty'] ?? null,
                'amount' => $validated['amount'] ?? null,
            ];

            $eventNote->update($newValues);

            $after = [];
            foreach ($trackedFields as $field) {
                $after[$field] = $eventNote->{$field};
            }

            $changedOld = [];
            $changedNew = [];
            foreach ($trackedFields as $field) {
                if ((string) ($before[$field] ?? '') !== (string) ($after[$field] ?? '')) {
                    $changedOld[$field] = $before[$field];
                    $changedNew[$field] = $after[$field];
                }
            }

            if (!empty($changedOld)) {
                AdminEventNoteHistory::create([
                    'admin_event_note_id' => $eventNote->id,
                    'changed_by' => (int) $request->user()->id,
                    'old_values' => $changedOld,
                    'new_values' => $changedNew,
                ]);
            }

            $this->storeAttachments($eventNote, $request->file('attachments', []));
        });

        return redirect()
            ->route('admin.event-notes.index')
            ->with('success', 'Nota administrativa actualizada correctamente.');
    }

    public function downloadAttachment(AdminEventNote $eventNote, AdminEventNoteAttachment $attachment)
    {
        if ($attachment->admin_event_note_id !== $eventNote->id) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($attachment->storage_path)) {
            abort(404, 'El archivo no existe o fue removido del almacenamiento.');
        }

        return Storage::disk('local')->download(
            $attachment->storage_path,
            $attachment->original_name
        );
    }

    private function storeAttachments(AdminEventNote $note, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('admin-event-notes/' . now()->format('Y/m'), 'local');

            AdminEventNoteAttachment::create([
                'admin_event_note_id' => $note->id,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }
}
