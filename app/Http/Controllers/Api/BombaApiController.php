<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bomba;
use Illuminate\Http\Request;

class BombaApiController extends Controller
{
    /**
     * Lista de bombas con su estado y última lectura de cada sensor.
     * Administrador ve todas las bombas; Técnico/Director solo las
     * de su propio centro de salud (centros_salud_id del usuario).
     */
    public function index(Request $request)
    {
        $usuario = $request->user();

        $query = Bomba::with([
            'centroSalud:id,nombre',
            'sensores.ultimaLectura',
        ]);

        // Filtrado por rol: solo el Administrador ve todos los centros.
        if (! $usuario->esAdministrador()) {
            $query->where('centros_salud_id', $usuario->centros_salud_id);
        }

        $bombas = $query->get()->map(function (Bomba $bomba) {
            return [
                'id' => $bomba->id,
                'codigo' => $bomba->codigo,
                'nombre' => $bomba->nombre,
                'encendido' => $bomba->encendido,
                'modo_operacion' => $bomba->modo_operacion,
                'estado' => $bomba->estado,
                'centro_salud' => $bomba->centroSalud?->nombre,
                'sensores' => $bomba->sensores->map(function ($sensor) {
                    return [
                        'id' => $sensor->id,
                        'tipo' => $sensor->tipo,
                        'nombre' => $sensor->nombre,
                        'unidad_medida' => $sensor->unidad_medida,
                        'valor_minimo' => $sensor->valor_minimo,
                        'valor_maximo' => $sensor->valor_maximo,
                        'ultima_lectura' => $sensor->ultimaLectura?->valor_medido,
                        'fecha_hora' => $sensor->ultimaLectura?->fecha_hora,
                    ];
                }),
            ];
        });

        return response()->json(['bombas' => $bombas]);
    }
}