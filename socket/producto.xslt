<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- Decimos que la salida sera HTML -->
<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<!-- Template principal: aplica al elemento raiz <mensaje> -->
<xsl:template match="/mensaje">
<html>
<head>
    <title>E-PeriTech - Resultado</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f0f4ff; }
        .tarjeta { background: white; padding: 20px; border-radius: 8px;
                   border-left: 5px solid #2563EB; max-width: 500px; }
        .titulo { color: #1B3A6B; font-size: 20px; font-weight: bold; }
        .campo { margin: 8px 0; color: #374151; }
        .precio { color: #16A34A; font-size: 18px; font-weight: bold; }
        .error { border-left-color: #DC2626; }
    </style>
</head>
<body>

<!-- Si la respuesta es OK, mostramos el producto -->
<xsl:if test="estado = 'OK'">
    <div class="tarjeta">
        <p class="titulo"><xsl:value-of select="datos/producto/nombre"/></p>
        <p class="campo">Categoria: <xsl:value-of select="datos/producto/categoria"/></p>
        <p class="precio">$<xsl:value-of select="datos/producto/precio"/></p>
        <p class="campo"><xsl:value-of select="datos/producto/descripcion"/></p>
    </div>
</xsl:if>

<!-- Si hay error, mostramos el mensaje de error -->
<xsl:if test="estado = 'ERROR'">
    <div class="tarjeta error">
        <p class="titulo">Error <xsl:value-of select="datos/codigo"/></p>
        <p class="campo"><xsl:value-of select="datos/descripcion"/></p>
    </div>
</xsl:if>

</body>
</html>
</xsl:template>
</xsl:stylesheet>
