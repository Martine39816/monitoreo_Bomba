<?php

namespace App\Support;

/**
 * Patrones de validacion reutilizables para evitar texto basura en los formularios:
 * espacios multiples, espacios al borde, o mezclas de caracteres sin sentido
 * (ej. "jglfds    0000000000gfdkb").
 *
 * Usar siempre junto con la regla 'string' y 'max:N' correspondiente, ej:
 *   'nombre' => ['required', 'string', 'max:100', 'regex:'.TextPatterns::NOMBRE_PERSONA],
 */
class TextPatterns
{
    /**
     * Nombre / apellido de una PERSONA: solo letras (con acentos y ñ) y espacios simples.
     * No permite numeros ni simbolos. No permite espacios dobles ni al borde.
     * Valido:   "Juan Perez", "Maria Jose", "Nuñez"
     * Invalido: "Juan123", "  Juan", "Juan  Perez" (doble espacio)
     */
    public const NOMBRE_PERSONA = "/^(?=\\S)(?!.*\\s{2,})[A-Za-zÁÉÍÓÚáéíóúÑñÜü']+(?:[ -][A-Za-zÁÉÍÓÚáéíóúÑñÜü']+)*(?<=\\S)$/u";

    /**
     * Nombre de un EQUIPO o LUGAR (bomba, sensor, centro de salud, dispositivo):
     * letras, numeros y espacios simples. Permite un guion como separador.
     * Valido:   "Bomba Norte 2", "Sensor A-01", "Centro Ticti Norte"
     * Invalido: "Bomba   Norte" (doble espacio), " Bomba", "Bomba#Norte"
     */
    public const NOMBRE_EQUIPO = "/^(?=\\S)(?!.*\\s{2,})[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü]+(?:[ \\-\\/\\.][A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü]+)*(?<=\\S)$/u";

    /**
     * Codigo tecnico (codigo interno, tanque_codigo): alfanumerico, sin espacios,
     * puede llevar guion, guion bajo o punto como separador.
     * Valido:   "BMB-001", "SEN_A01", "TQ.01"
     * Invalido: "BMB 001" (espacio), "BMB#001"
     */
    public const CODIGO = '/^[A-Za-z0-9\-_.]+$/';

    /**
     * Marca / modelo / serie: alfanumerico y espacios simples (los fabricantes
     * suelen mezclar letras y numeros, ej. "Grundfos CR 15", "XJ-40/2").
     * No permite espacios dobles ni al borde.
     */
    public const MARCA_MODELO_SERIE = "/^(?=\\S)(?!.*\\s{2,})[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü]+(?:[ \\-\\/\\.][A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü]+)*(?<=\\S)$/u";

    /**
     * Direccion fisica: mas permisiva (numeros, #, comas, puntos) pero sin
     * espacios dobles ni al borde.
     */
    public const DIRECCION = "/^(?=\\S)(?!.*\\s{2,})[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü#.,-]+(?:[ ][A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü#.,-]+)*(?<=\\S)$/u";

    /**
     * Telefono celular boliviano: exactamente 8 digitos, empieza con 6 o 7.
     * Valido:   "71234567", "60123456"
     * Invalido: "12345678", "+59171234567", "7123-4567"
     */
    public const TELEFONO_BOLIVIA = '/^[67][0-9]{7}$/';

    /** Mensajes de error en español, listos para usar en $request->validate([...], [...]) */
    public static function mensajes(): array
    {
        return [
            'regex' => 'El formato de :attribute no es válido.',
        ];
    }
}