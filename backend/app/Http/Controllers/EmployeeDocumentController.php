<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentController extends Controller
{
    public function index(Employee $employee)
    {
        return response()->json([
            'status' => 'success',
            'data' => $employee->documents()->latest()->get(),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:10240',
        ]);

        $uploadedFile = $validated['file'];
        $path = $uploadedFile->store('employees/documents', 'public');

        $document = $employee->documents()->create([
            'title' => $validated['title'],
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'file_size' => $uploadedFile->getSize(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document uploaded successfully.',
            'data' => $document->fresh(),
        ], 201);
    }

    public function update(Request $request, Employee $employee, int $documentId)
    {
        $document = $employee->documents()->findOrFail($documentId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'nullable|file|max:10240',
        ]);

        $payload = ['title' => $validated['title']];

        if ($request->hasFile('file')) {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }

            $uploadedFile = $validated['file'];
            $payload['file_path'] = $uploadedFile->store('employees/documents', 'public');
            $payload['mime_type'] = $uploadedFile->getClientMimeType();
            $payload['file_size'] = $uploadedFile->getSize();
        }

        $document->update($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Document updated successfully.',
            'data' => $document->fresh(),
        ]);
    }

    public function destroy(Employee $employee, int $documentId)
    {
        $document = $employee->documents()->findOrFail($documentId);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully.',
        ]);
    }
}
