<?php

namespace App\Http\Controllers;

use App\Models\JobNote;
use App\Services\DocumentPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Job notes: devices received for repair, with the customer's on-screen signature.
 */
class JobNoteController extends Controller
{
    public function index(): View
    {
        return view('jobs.index', [
            'jobs' => JobNote::select('id', 'job_no', 'customer_name', 'contact_person', 'contact_no', 'item', 'serial_no', 'collected_date')
                ->selectRaw('customer_signature IS NOT NULL AS signed')
                ->orderByDesc('job_no')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('jobs.form', [
            'job' => new JobNote([
                'customer_name' => $request->query('customer_name', ''),
                'accessories_received' => 'Device only',
                'collected_date' => now()->toDateString(),
                'collected_by' => $request->user()->username,
                'terms_conditions' => config('sjpos.job.default_terms'),
            ]),
            'displayJobNo' => JobNote::nextNumber(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function show(JobNote $job): View
    {
        return view('jobs.show', compact('job'));
    }

    public function edit(JobNote $job): View
    {
        return view('jobs.form', ['job' => $job, 'displayJobNo' => $job->job_no]);
    }

    public function update(Request $request, JobNote $job): JsonResponse
    {
        return $this->save($request, $job->id);
    }

    public function destroy(JobNote $job): JsonResponse
    {
        $job->delete();

        return response()->json(['status' => 'success', 'message' => 'Job note deleted.']);
    }

    public function pdf(JobNote $job, DocumentPdf $pdfs): Response
    {
        [$fileName, $pdf] = $pdfs->jobNote($job);

        return $pdf->stream($fileName);
    }

    /**
     * Capture the customer's signature for an existing, unsigned job note.
     */
    public function sign(Request $request, JobNote $job): JsonResponse
    {
        $signature = JobNote::normaliseSignature($request->input('customer_signature'));
        if ($signature === null) {
            return response()->json(['status' => 'error', 'message' => 'Please sign before saving.'], 400);
        }

        $updated = JobNote::whereKey($job->id)->whereNull('customer_signature')->update([
            'customer_signature' => $signature,
            'signed_name' => trim((string) $request->input('signed_name')) ?: ($job->contact_person ?: $job->customer_name),
            'signed_at' => now(),
        ]);

        if ($updated === 0) {
            return response()->json(['status' => 'error', 'message' => 'This job note is already signed or no longer exists.'], 409);
        }

        return response()->json(['status' => 'success', 'message' => 'Signature saved.']);
    }

    /**
     * Create a job note, or update one when $id is given.
     */
    private function save(Request $request, ?int $id): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'item' => ['required', 'string', 'max:255'],
            'serial_no' => ['nullable', 'string', 'max:255'],
            'fault' => ['required', 'string'],
            'accessories_received' => ['nullable', 'string', 'max:255'],
            'device_condition' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'collected_date' => ['required', 'date_format:Y-m-d'],
            'collected_by' => ['required', 'string', 'max:255'],
            'terms_conditions' => ['nullable', 'string'],
        ]);
        $data['terms_conditions'] = trim((string) ($data['terms_conditions'] ?? '')) ?: config('sjpos.job.default_terms');

        $newSignature = null;
        if (filled($request->input('customer_signature'))) {
            $newSignature = JobNote::normaliseSignature($request->input('customer_signature'));
            if ($newSignature === null) {
                throw ValidationException::withMessages([
                    'customer_signature' => 'The signature could not be read. Please clear it and sign again.',
                ]);
            }
        }
        $signedName = trim((string) $request->input('signed_name')) ?: $data['customer_name'];

        $job = DB::transaction(function () use ($request, $id, $data, $newSignature, $signedName) {
            if ($id === null) {
                // Allocate the job number server-side so two tablets can't take the same one
                return JobNote::create($data + [
                    'job_no' => JobNote::nextNumber(lock: true),
                    'customer_signature' => $newSignature,
                    'signed_name' => $newSignature ? $signedName : null,
                    'signed_at' => $newSignature ? now() : null,
                ]);
            }

            $job = JobNote::lockForUpdate()->findOrFail($id);

            // Keep the existing signature unless staff chose to remove it.
            // A new signature can only be added to a job note that isn't signed.
            if ($job->is_signed && $request->input('clear_signature') === '1') {
                $job->fill(['customer_signature' => null, 'signed_name' => null, 'signed_at' => null]);
            }
            if (! $job->is_signed && $newSignature !== null) {
                $job->fill(['customer_signature' => $newSignature, 'signed_name' => $signedName, 'signed_at' => now()]);
            }

            $job->fill($data)->save();

            return $job;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Job note '.$job->formatted_no.($id === null ? ' saved.' : ' updated.'),
            'id' => $job->id,
            'job_no' => $job->job_no,
            'redirect' => route('jobs.show', $job),
        ]);
    }
}
