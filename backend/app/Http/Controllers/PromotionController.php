<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;

class PromotionController extends Controller
{
    public function index()
    {
        return response()->json(Promotion::all());
    }

    public function store(Request $request)
    {
        $promo = Promotion::create([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'value' => $request->value,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
            'status' => $request->status,
        ]);

        return response()->json($promo);
    }

    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);

        $promo->update([
            'name' => $request->name,
            'code' => $request->code,
            'type' => $request->type,
            'value' => $request->value,
            'start_date' => $request->startDate,
            'end_date' => $request->endDate,
            'status' => $request->status,
        ]);

        return response()->json($promo);
    }

    public function destroy($id)
    {
        Promotion::destroy($id);
        return response()->json(['message' => 'deleted']);
    }
}