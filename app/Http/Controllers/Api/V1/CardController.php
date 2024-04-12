<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cards = Card::all();

        return response()->json(['data' => $cards]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'img' => 'required|image',  // Asegúrate de que es un archivo de imagen
            'state' => 'required|in:Coming Soon,Available',
            'date' => 'required|date',
            'description' => 'sometimes|nullable|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity
        }
    
        // Si la validación es exitosa, procede con la lógica para guardar el modelo
        $card = new Card($validator->validated());
        
        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            // Guarda la imagen en el disco configurado y guarda la ruta en el modelo
            $path = $request->img->store('images', 'public');
            $card->img = $path;  // Guarda la ruta en la base de datos
        }
    
        $card->save();
    
        return response()->json([
            'message' => 'Card created successfully!',
            'data' => $card
        ], 201); // 201 Created
    
    
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
{
    $card = Card::find($id);

    if (!$card) {
        return response()->json(['message' => 'Card not found'], 404);
    }

    return response()->json(['data' => $card]);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $card = Card::find($id);

        if (!$card) {
            return response()->json(['message' => 'Card not found'], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'img' => 'required|image',
            'state' => 'required|in:Coming Soon,Available',
            'date' => 'required|date',
            'description' => 'nullable|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $card->fill($validator->validated());
        $card->save();
    
        return response()->json([
            'message' => 'Card updated successfully',
            'data' => $card
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $card = Card::find($id);

    if (!$card) {
        return response()->json(['message' => 'Card not found'], 404);
    }

    $card->delete();

    return response()->json(['message' => 'Card deleted successfully']);
    }
}
