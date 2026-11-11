<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Reportes del sistema</h2></x-slot>
    <div class="py-6"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
        <div class="bg-white p-5 shadow rounded-lg">
            <form method="GET" action="{{ route('reportes.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div><label class="text-sm">Reporte</label><select name="tipo" class="w-full rounded border-gray-300">
                    @foreach(['alertas'=>'Alertas','consumo'=>'Consumo estimado','mantenimientos'=>'Mantenimientos','bombas'=>'Estado de bombas','niveles'=>'Niveles del tanque'] as $k=>$v)
                        <option value="{{ $k }}" @selected($tipo===$k)>{{ $v }}</option>
                    @endforeach
                </select></div>
                <div><label class="text-sm">Centro</label><select name="centro_id" class="w-full rounded border-gray-300"><option value="">Todos los visibles</option>
                    @foreach($centros as $c)<option value="{{ $c->id }}" @selected((string)$centroId===(string)$c->id)>{{ $c->nombre }}</option>@endforeach
                </select></div>
                <div><label class="text-sm">Desde</label><input type="date" name="desde" value="{{ $desde->format('Y-m-d') }}" class="w-full rounded border-gray-300"></div>
                <div><label class="text-sm">Hasta</label><input type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}" class="w-full rounded border-gray-300"></div>
                <div class="flex items-end"><button class="w-full bg-blue-700 text-white rounded px-4 py-2">Consultar</button></div>
            </form>
        </div>
        <div class="bg-white p-5 shadow rounded-lg">
            <div class="flex flex-wrap justify-between gap-3 mb-4">
                <div><h3 class="font-semibold text-lg">{{ $titulo }}</h3><p class="text-sm text-gray-500">{{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</p></div>
                <div class="flex gap-2"><a class="px-3 py-2 rounded bg-green-700 text-white" href="{{ request()->fullUrlWithQuery(['exportar'=>'csv']) }}">Exportar Excel (CSV)</a><button onclick="window.print()" class="px-3 py-2 rounded bg-gray-700 text-white">Guardar PDF</button></div>
            </div>
            <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr>
                @if($filas->isNotEmpty()) @foreach(array_keys($filas->first()) as $col)<th class="text-left border-b p-2 bg-gray-50">{{ $col }}</th>@endforeach @endif
            </tr></thead><tbody>
                @forelse($filas as $fila)<tr>@foreach($fila as $valor)<td class="border-b p-2">{{ $valor ?? '—' }}</td>@endforeach</tr>
                @empty<tr><td class="p-4 text-gray-500">No existen registros para los filtros seleccionados.</td></tr>@endforelse
            </tbody></table></div>
            <p class="mt-4 text-xs text-gray-500">Los valores de consumo son estimaciones del sistema. No representan medición directa de un caudalímetro.</p>
        </div>
    </div></div>
    <style>@media print { nav, header, form, button, a { display:none !important } body { background:white } .shadow { box-shadow:none } }</style>
</x-app-layout>
