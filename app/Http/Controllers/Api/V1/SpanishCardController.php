<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SpanishCard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SpanishCardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cards = SpanishCard::all();

        return $cards;
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
        $card = new SpanishCard($validator->validated());
        $card->save();

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'card-' . $card->card_id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            // Cambio: Usar el disco 's3' para almacenar el archivo
            $path = $request->file('img')->storeAs('images', $filename, 's3');

            // Guardar la URL completa del archivo en S3
            $card->img = 'https://elephant-bucket-s3.s3.us-east-2.amazonaws.com/' . $path;
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
        $card = SpanishCard::find($id);

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
        $card = SpanishCard::find($id);

        if (!$card) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }


        // La URL completa del objeto en S3.
        $s3Url = $card->img;

        // Parsear la URL para obtener la clave del objeto S3.
        // Suponiendo que la URL es como "https://s3.region.amazonaws.com/bucket-name/images/filename.jpg"
        $parsedUrl = parse_url($s3Url);
        $s3Key = ltrim($parsedUrl['path'], '/');  // Elimina el slash inicial si está presente.

        // Eliminar la imagen de S3.
        if (Storage::disk('s3')->exists($s3Key)) {
            Storage::disk('s3')->delete($s3Key);
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

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'card-' . $card->card_id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            // Cambio: Usar el disco 's3' para almacenar el archivo
            $path = $request->file('img')->storeAs('images', $filename, 's3');

            // Guardar la URL completa del archivo en S3
            $card->img = 'https://elephant-bucket-s3.s3.us-east-2.amazonaws.com/' . $path;
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
        $card = SpanishCard::find($id);

        if (!$card) {
            return response()->json(['message' => 'Card not found'], 404);
        }

        // La URL completa del objeto en S3.
        $s3Url = $card->img;

        // Parsear la URL para obtener la clave del objeto S3.
        // Suponiendo que la URL es como "https://s3.region.amazonaws.com/bucket-name/images/filename.jpg"
        $parsedUrl = parse_url($s3Url);
        $s3Key = ltrim($parsedUrl['path'], '/');  // Elimina el slash inicial si está presente.

        // Eliminar la imagen de S3.
        if (Storage::disk('s3')->exists($s3Key)) {
            Storage::disk('s3')->delete($s3Key);
        }

        $card->delete();

        return response()->json(['message' => 'Card deleted successfully'], 200); // 200 OK
    }
}
