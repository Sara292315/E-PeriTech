<?php
// ============================================================
//  E-PeriTech — Clase Serializable: Producto
// ============================================================
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 1: Planteamiento del Escenario
//  Esta clase representa la entidad central del sistema
//  E-PeriTech: el Producto tecnologico. Es el objeto de
//  negocio sobre el que giran todas las operaciones del
//  sistema distribuido (consulta, comparacion, compra).
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 3: Delimitacion de Procesos Remotos
//  Esta clase es el modelo de datos que viaja entre el
//  cliente y el servidor como proceso remoto de negocio.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #1 - Actividad 4: Diseno del Payload
//  Las propiedades (id, nombre, categoria, precio,
//  descripcion) corresponden a los campos del payload
//  JSON definido en la Guia #1.
// --------------CLIENTE SERVIDOR-------------------------
//  GUIA #5 - Actividad 1: Definicion de Interfaces y
//  Clases Serializables. Esta clase es el objeto de
//  intercambio entre cliente y servidor. Debe ser
//  identica en ambos extremos para que el Marshaling
//  (serialize/unserialize) funcione correctamente.
//  GUÍA 5 — Actividad 1
//  Este archivo es idéntico en cliente y servidor.
// ============================================================

class Producto {
    public int    $id;
    public string $nombre;
    public string $categoria;
    public float  $precio;
    public string $descripcion;

    public function __construct(
        int    $id          = 0,
        string $nombre      = '',
        string $categoria   = '',
        float  $precio      = 0.0,
        string $descripcion = ''
    ) {
        $this->id          = $id;
        $this->nombre      = $nombre;
        $this->categoria   = $categoria;
        $this->precio      = $precio;
        $this->descripcion = $descripcion;
    }

    //  --------------CLIENTE SERVIDOR-------------------------
    //  GUIA #5 - Actividad 1 y Actividad 3: metodo auxiliar
    //  para inspeccion del objeto reconstruido (Unmarshaling).
    //  Valida que el estado original se mantiene tras
    //  la deserializacion en el servidor.
    // ---------------------------------------------------------
    public function mostrar(): string {
        return sprintf(
            "[ID:%d] %s | Categoria: %s | Precio: $%.2f | Desc: %s",
            $this->id,
            $this->nombre,
            $this->categoria,
            $this->precio,
            $this->descripcion
        );
    }
}
