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
            'img' => 'required|image',  // Asegúrate de que es un archivo de imagen
            'price' => 'required|numeric',
            'status' => 'required|in:Available,Out of stock,Sold out,Coming Soon'
        ]);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity
        }

        // Si la validación es exitosa, procede con la lógica para guardar el modelo

        $item = new Item($validator->validated());

        if ($request->hasFile('img') && $request->file('img')->isValid()) {
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-'.time().'-'.Str::random(10).'.'.$extension;
            // Aquí usamos el método store() que automáticamente pone el archivo en el directorio 'images' en el disco 'public'
            $path = $request->file('img')->storeAs('images', $filename, 'public');
        
            // Guardamos la ruta relativa, considerando el enlace simbólico 'storage' en la carpeta 'public'
            $item->img = 'images/'.$filename;
            $item->save();
        }

        return response()->json([
            'message' => 'Item created successfully!',
            'data' => $item
        ], 201); // 201 Created

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = Item::find($id);

        if(!$item) {
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

        if(!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        // Define las reglas de validación. `sometimes` se añade para permitir actualizaciones parciales
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'img' => 'sometimes|image',
            'price' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|in:Available,Out of stock,Sold out,Coming Soon'
        ];

        // Validación de la solicitud
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Actualizar los campos del ítem con los datos validados que estén presentes
        $item->fill($validator->validated());

        // Verificar si hay una nueva imagen para subir
        if($request->hasFile('img') && $request->file('img')->isValid()) {
            // Eliminar la imagen anterior si existe
            if($item->img) {
                $imagePath = $item->img;
                if (!Str::startsWith($imagePath, 'public/')) {
                    $imagePath = 'public/' . $imagePath;
                }
            
                // Usar el Storage facade para eliminar la imagen
                if (Storage::exists($imagePath)) {
                    Storage::delete($imagePath);
                }
            }

            // Subir y almacenar la nueva imagen
            $extension = $request->file('img')->getClientOriginalExtension();
            $filename = 'item-' . time() . '-' . Str::random(10) . '.' . $extension;
            $path = $request->file('img')->storeAs('images', $filename, 'public');

            // Actualizar la propiedad 'img' con la nueva ruta de la imagen
            $item->img = $path; // 'images/' . $filename; si quieres incluir el subdirectorio 'images'
        }

        // Guardar los cambios del ítem en la base de datos
        $item->save();

        // Devolver una respuesta JSON con el ítem actualizado
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
        $item = Item::find($id);

        if(!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404); // 404 Not Found
        }

        if($item) {
            //Comprueba si una imagen existe y la elimina
            $imagePath = $item->img;
            if (!Str::startsWith($imagePath, 'public/')) {
                $imagePath = 'public/' . $imagePath;
            }
        
            // Usar el Storage facade para eliminar la imagen
            if (Storage::exists($imagePath)) {
                Storage::delete($imagePath);
            }
        

            
        }

        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully!'
        ], 200); // 200 OK
    }
}
