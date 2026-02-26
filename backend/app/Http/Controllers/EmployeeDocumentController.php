<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\MediaStorageService;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    public function __construct(private readonly MediaStorageService $mediaStorage) {}

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
        $path = $this->mediaStorage->storeUploadedFile(
            $uploadedFile,
            'employees/documents',
            'public',
            'raw'
        );

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
            $this->mediaStorage->deleteStoredFile($document->file_path, 'public', 'raw');

            $uploadedFile = $validated['file'];
            $payload['file_path'] = $this->mediaStorage->storeUploadedFile(
                $uploadedFile,
                'employees/documents',
                'public',
                'raw'
            );
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

        $this->mediaStorage->deleteStoredFile($document->file_path, 'public', 'raw');

        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully.',
        ]);
    }
}
