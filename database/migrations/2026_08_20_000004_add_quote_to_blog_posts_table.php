<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cita destacada del cuerpo del artículo (texto + atribución).
     *
     * NOTA — esto se aparta a propósito de lo que dice
     * `docs/rebrand/inventario/spec-03-blog.md` §5.2/§9: esa spec decide
     * "sin columna nueva", dejando la cita como un <blockquote> suelto
     * dentro del WYSIWYG de `body`, con la atribución como convención de
     * marcado (<cite> o un último <p>) que el equipo de contenido debe
     * seguir a mano en cada post. El brief de este lote pidió explícitamente
     * el campo, y el criterio de backend coincide en que vale la pena:
     * depender de que cada redactor recuerde escribir el HTML exacto
     * (comillas sin cursiva, atribución en línea aparte alineada a la
     * derecha) es frágil y no da nada que un backend pueda validar o
     * mostrar/ocultar de forma consistente. Un campo estructurado nullable
     * garantiza la forma exacta sin impedir que, además, un editor siga
     * pegando un blockquote suelto dentro del cuerpo si prefiere esa vía —
     * ambos caminos coexisten, este es el que la ficha puede confiar en
     * mostrar/ocultar con un solo `if`.
     *
     * `quote_text_*` es traducible (una per locale, como `body_*`) porque es
     * un extracto del propio cuerpo traducible. `quote_attribution` NO tiene
     * variantes de idioma — mismo criterio ya usado para `author_role` en
     * esta misma tabla (2026_08_19_000002): es un nombre + rol corto, no
     * prosa que cambie de idioma en el 90% de los casos reales.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->text('quote_text_es')->nullable()->after('features');
            $table->text('quote_text_en')->nullable()->after('quote_text_es');
            $table->text('quote_text_pt')->nullable()->after('quote_text_en');
            $table->string('quote_attribution')->nullable()->after('quote_text_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['quote_text_es', 'quote_text_en', 'quote_text_pt', 'quote_attribution']);
        });
    }
};
