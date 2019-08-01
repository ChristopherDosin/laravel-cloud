<?php

namespace App\Http\Controllers\API;

use App\Database;
use App\DatabaseBackup;
use App\StorageProvider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\CreateDatabaseBackupRequest;

class DatabaseBackupController extends Controller
{
    /**
     * Get all of the backups for the given database.
     *
     * @param  Request  $request
     * @param  \App\Database  $database
     * @return Response
     */
    public function index(Request $request, Database $database)
    {
        $this->authorize('view', $database->project);

        return $database->backups()->with('storageProvider')
                ->when($request->database_name, function ($query, $name) {
                    $query->where('database_name', $name);
                })->get()->groupBy('database_name');
    }

    /**
     * Create a new backup for the database.
     *
     * @param  \App\Http\Requests\CreateDatabaseBackupRequest  $request
     * @param  \App\Database  $database
     * @return Response
     */
    public function store(CreateDatabaseBackupRequest $request, Database $database)
    {
        $this->authorize('view', $database->project);

        if (! $database->isProvisioned()) {
            throw ValidationException::withMessages([
                'database' => ['This database has not finished provisioning.'],
            ]);
        }

        return response()->json(
            $database->backup(
                $request->storageProvider(),
                $request->database_name
            ), 201
        );
    }

    /**
     * Destroy the given database backup.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return Response
     */
    public function destroy(DatabaseBackup $backup)
    {
        $this->authorize('delete', $backup);

        $backup->delete();
    }
}
