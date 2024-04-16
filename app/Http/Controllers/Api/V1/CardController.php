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
        $card->save();

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'card-' . $card->id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            // Aquí usamos el método store() que automáticamente pone el archivo en el directorio 'images' en el disco 'public'
            $path = $request->file('img')->storeAs('images', $filename, 'public');

            // Guardamos la ruta relativa, considerando el enlace simbólico 'storage' en la carpeta 'public'
            $card->img = 'images/' . $filename;
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

        $card = Card::find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }


        //Comprueba si una imagen existe y la elimina
        $imagePath = $card->img;
        if (!Str::startsWith($imagePath, 'public/')) {
            $imagePath = 'public/' . $imagePath;
        }

        // Usar el Storage facade para eliminar la imagen
        if (Storage::exists($imagePath)) {
            Storage::delete($imagePath);
        }


        // Define las reglas de validación. `sometimes` se añade para permitir actualizaciones parciales
        $rules = [
            'title' => 'sometimes|string|max:255',
            'img' => 'sometimes|image',  // Asegúrate de que es un archivo de imagen
            'state' => 'sometimes|in:Coming Soon,Available',
            'date' => 'sometimes|date',
            'description' => 'sometimes|nullable|string'
        ];

        // Validación de la solicitud
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Actualizar los campos del ítem con los datos validados que estén presentes
        $card->fill($validator->validated());

        // Verificar si hay una nueva imagen para subir
        if ($request->hasFile('img') && $request->file('img')->isValid()) {

            // Subir y almacenar la nueva imagen
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'card-' . $card->id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            $path = $request->file('img')->storeAs('images', $filename, 'public');

            // Actualizar la propiedad 'img' con la nueva ruta de la imagen
            $card->img = $path; // 'images/' . $filename; si quieres incluir el subdirectorio 'images'
        }

        // Guardar los cambios del ítem en la base de datos
        $card->save();

        // Devolver una respuesta JSON con el ítem actualizado
        return response()->json([
            'message' => 'Item updated successfully!',
            'data' => $card
        ], 200);
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

        if ($card) {
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
