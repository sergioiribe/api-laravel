<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


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
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-'.time().'-'.Str::random(10).'.'.$extension;
            // Aquí usamos el método store() que automáticamente pone el archivo en el directorio 'images' en el disco 'public'
            $path = $request->file('img')->storeAs('images', $filename, 'public');
        
            // Guardamos la ruta relativa, considerando el enlace simbólico 'storage' en la carpeta 'public'
            $card->img = 'images/'.$filename;
            $card->save();
        }
    
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

        if($card) {
            //Comprueba si una imagen existe y la elimina
            $imagePath = $card->img;
            if (!Str::startsWith($imagePath, 'public/')) {
                $imagePath = 'public/' . $imagePath;
            }
        
            // Usar el Storage facade para eliminar la imagen
            if (Storage::exists($imagePath)) {
                Storage::delete($imagePath);
            }
        

            
        }

        $card->delete();

        return response()->json(['message' => 'Card deleted successfully'], 200); // 200 OK
    }
}
