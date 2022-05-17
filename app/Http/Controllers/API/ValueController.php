<?php

namespace App\Http\Controllers\API;

use App\Models\Value;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ValueController extends Controller
{
    /**
     * Get all values
     */
    public function index(Request $request)
    {
        if ($request->keys) {
            $keys = explode(',', $request->keys);
        } else {
            $keys = null;
        }
     
        if (cache('values')) {
            $data = cache('values');
        } else {
            $data = $this->loadFromDB($keys);
            cache('values', $data, 300);
        }

        if ($data->isEmpty()) {
            return response()->json([
                'values' => [],
                'status' => 'success',
            ], 200);
        }

        $values = $data->pluck('value', 'key');

        return response()->json([
            'values' => $values,
            'status' => 'success'
        ], 200);
    }

    /**
     * Store values
     */
    public function store(Request $request)
    {
        $data = $request->all();

        if (empty($data)) {
            return response()->json([
            'error' => 'Nothing to store',
        ], 400);
        }

        $count = Value::insertOrIgnore($data);

        if ($count === 0) {
            return response()->json([
                'error' => 'Nothing to store',
            ], 400);
        }


        return response()->json([
            'status' => 'Success',
            'message' => 'Successfully Stored',
        ], 201);
    }

    /**
     * Update values
     */
    public function update(Request $request)
    {
        $data = $request->all();
        $expiresAt = $this->getExpiresAt();
        $values = Value::whereIn('key', array_keys($data))->get();

        if ($values->isEmpty()) {
            return response()->json([
                'error' => 'Nothing to update',
            ], 400);
        }

        DB::transaction(function () use ($values, $data, $expiresAt) {
            foreach ($values as $value) {
                $value->value = $data[$value->key];
                $value->expires_at = $expiresAt;
                $value->save();
            }
        }, 2);

        return response()->json([
            'values' => $values,
            'status' => 'success',
            'message' => 'Successfully Updated',
        ], 200);
    }

    /**
     * Load values from DB
     */
    private function loadFromDB($keys)
    {
        if (is_null($keys)) {
            $data = DB::table('values')
                        ->select('id', 'key', 'value')
                        ->get();
        } else {
            $data = DB::table('values')
                        ->select('id', 'key', 'value')
                        ->whereIn('key', $keys)
                        ->get();
        }

        return $data;
    }

    /**
     * Get expires_at
     */
    private function getExpiresAt()
    {
        $ttl = config('app.ttl');

        return now()->addMinutes($ttl);
    }
}
