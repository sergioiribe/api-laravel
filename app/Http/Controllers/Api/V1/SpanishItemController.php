<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SpanishItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SpanishItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = SpanishItem::all();

        return $items;

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'img' => 'required|image',
            'price' => 'required|numeric',
            'status' => 'required|in:Available,Out of stock,Sold out,Coming Soon'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $item = new SpanishItem($validator->validated());
        $item->save();

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-' . $item->item_id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            // Cambio: Usar el disco 's3' para almacenar el archivo
            $path = $request->file('img')->storeAs('images', $filename, 's3');

            // Guardar la URL completa del archivo en S3
            $item->img = 'https://elephant-bucket-s3.s3.us-east-2.amazonaws.com/' . $path;
            $item->save();
        }

        return response()->json([
            'message' => 'Item created successfully!',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = SpanishItem::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }

        return response()->json(['data' => $item]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $item = SpanishItem::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }

        // La URL completa del objeto en S3.
        $s3Url = $item->img;

        // Parsear la URL para obtener la clave del objeto S3.
        // Suponiendo que la URL es como "https://s3.region.amazonaws.com/bucket-name/images/filename.jpg"
        $parsedUrl = parse_url($s3Url);
        $s3Key = ltrim($parsedUrl['path'], '/');  // Elimina el slash inicial si está presente.

        // Eliminar la imagen de S3.
        if (Storage::disk('s3')->exists($s3Key)) {
            Storage::disk('s3')->delete($s3Key);
        }

        // Reglas de validación, `sometimes` permite actualizaciones parciales
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'img' => 'sometimes|image',
            'price' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|in:Available,Out of stock,Sold out,Coming Soon'
        ];

        // Validación de la solicitud
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Actualiza los campos del item con los datos validados
        $item->fill($validator->validated());

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-' . $item->item_id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            // Cambio: Usar el disco 's3' para almacenar el archivo
            $path = $request->file('img')->storeAs('images', $filename, 's3');

            // Guardar la URL completa del archivo en S3
            $item->img = 'https://elephant-bucket-s3.s3.us-east-2.amazonaws.com/' . $path;
        }

        // Guardar los cambios en la base de datos
        $item->save();

        // Devolver una respuesta JSON con el item actualizado
        return response()->json([
            'message' => 'Item updated successfully!',
            'data' => $item
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = SpanishItem::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        // La URL completa del objeto en S3.
        $s3Url = $item->img;

        // Parsear la URL para obtener la clave del objeto S3.
        // Suponiendo que la URL es como "https://s3.region.amazonaws.com/bucket-name/images/filename.jpg"
        $parsedUrl = parse_url($s3Url);
        $s3Key = ltrim($parsedUrl['path'], '/');  // Elimina el slash inicial si está presente.

        // Eliminar la imagen de S3.
        if (Storage::disk('s3')->exists($s3Key)) {
            Storage::disk('s3')->delete($s3Key);
        }

        // Eliminar el ítem de la base de datos.
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully!'
        ], 200);
    }
}
