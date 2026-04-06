<?php
// ============================================================
//  E-PeriTech — Clase Serializable: Producto
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
