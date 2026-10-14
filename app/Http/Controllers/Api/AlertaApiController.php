<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\Request;

class AlertaApiController extends Controller
{
    /**
     * Lista de alertas, más recientes primero.
     * Administrador ve todas; Técnico/Director solo las de bombas
     * de su propio centro de salud.
     * Parámetro opcional: ?pendientes=1 para ver solo las no atendidas.
     */
    public function index(Request $request)
    {
        $usuario = $request->user();

        $query = Alerta::with(['bomba:id,codigo,nombre,centros_salud_id', 'sensor:id,nombre', 'usuarioAtencion'])
            ->orderBy('fecha_hora', 'desc');

        if (! $usuario->esAdministrador()) {
            $query->whereHas('bomba', function ($q) use ($usuario) {
                $q->where('centros_salud_id', $usuario->centros_salud_id);
            });
        }

        if ($request->boolean('pendientes')) {
            $query->where('atendida', false);
        }

        $alertas = $query->limit(50)->get()->map(fn (Alerta $alerta) => $this->formatear($alerta));

        return response()->json(['alertas' => $alertas]);
    }

    /**
     * Marca una alerta como solucionada. Misma lógica que el panel web
     * (AlertaController@atender), más la verificación de centro de salud.
     */
    public function solucionar(Request $request, Alerta $alerta)
    {
        $usuario = $request->user();
        $alerta->load('bomba:id,codigo,nombre,centros_salud_id');

        if (! $usuario->esAdministrador()
            && $alerta->bomba?->centros_salud_id !== $usuario->centros_salud_id) {
            return response()->json(['mensaje' => 'No tienes permiso sobre esta alerta.'], 403);
        }

        // Si ya estaba atendida no se sobrescribe quién la atendió ni cuándo.
        if (! $alerta->atendida) {
            $alerta->update([
                'atendida' => true,
                'fecha_atencion' => now(),
                'usuario_atencion_id' => $usuario->id,
            ]);
            $alerta->load('usuarioAtencion', 'sensor:id,nombre');
        }

        return response()->json(['alerta' => $this->formatear($alerta)]);
    }

    private function formatear(Alerta $alerta): array
    {
        return [
            'id' => $alerta->id,
            'tipo' => $alerta->tipo,
            'descripcion' => $alerta->descripcion,
            'valor_detectado' => $alerta->valor_detectado,
            'valor_permitido' => $alerta->valor_permitido,
            'fecha_hora' => $alerta->fecha_hora,
            'atendida' => $alerta->atendida,
            'fecha_atencion' => $alerta->fecha_atencion,
            'atendida_por' => $alerta->usuarioAtencion?->name,
            'bomba' => $alerta->bomba ? [
                'id' => $alerta->bomba->id,
                'codigo' => $alerta->bomba->codigo,
                'nombre' => $alerta->bomba->nombre,
            ] : null,
            'sensor' => $alerta->sensor?->nombre,
        ];
    }
}