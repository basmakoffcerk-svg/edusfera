<?php

namespace App\Http\Controllers;

use App\Models\PromoLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PromoLeadController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|min:2',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = PromoLead::create([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'subject' => $request->input('subject'),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ваша заявка успешно отправлена!'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отправке заявки. Пожалуйста, попробуйте позже.'
            ], 500);
        }
    }
}
