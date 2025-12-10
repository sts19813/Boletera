<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileUploadService
{
    /**
     * Sube un archivo al folder especificado dentro de public y devuelve la ruta relativa.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        // Crear nombre único para el archivo
        $filename = time() . '_' . $file->getClientOriginalName();

        // Mover el archivo a public/$folder
        $file->move(public_path($folder), $filename);

        // Retornar la ruta relativa
        return "$folder/$filename";
    }
}
