<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;




class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $items = Item::all();

        return response()->json(['data' => $items]);
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

        $item = new Item($validator->validated());
        $item->save();

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-' . $item->id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
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
        $item = Item::find($id);

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
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }

        // Comprueba si una imagen existe en S3 y la elimina
        if ($item->img) {
            $existingImagePath = $item->img; // Asumimos que esto es una ruta relativa en S3
            if (Storage::disk('s3')->exists($existingImagePath)) {
                Storage::disk('s3')->delete($existingImagePath);
            }
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

        // Verificar si hay una nueva imagen para subir
        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            // Subir y almacenar la nueva imagen en S3
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-' . $item->id . '-' . time() . '-' . Str::random(10) . '.' . $extension;
            $path = $request->file('img')->storeAs('images', $filename, 's3');

            // Guardar la ruta relativa de la imagen en S3 en la propiedad 'img'
            $item->img = $path;
        }

        // Guardar los cambios en la base de datos
        $item->save();

        // Devolver una respuesta JSON con el item actualizado
        return response()->json([
            'message' => 'Item updated successfully!',
            'data' => $item
        ], 200);
    }


    public function destroy($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }

        // Asumiendo que 'img' almacena la ruta relativa en el bucket de S3
        // que es lo que devuelve el método storeAs
        $imagePath = $item->img;

        // Usar el Storage facade para eliminar la imagen del disco S3
        if (Storage::disk('s3')->exists('images/item-3-1713391638-PvpiUoWsRl.jpg')) {
            Storage::disk('s3')->delete('images/item-3-1713391638-PvpiUoWsRl.jpg');
        }

        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully!'
        ], 200); // 200 OK
    }
}
