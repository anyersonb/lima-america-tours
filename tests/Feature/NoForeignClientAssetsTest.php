<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lima América NO puede publicar material de Lima View Tours.
 *
 * Contexto: los dos son clientes distintos y competencia directa entre sí. Este
 * proyecto nació como fork del motor de Lima View, así que su carpeta de
 * imágenes llegó con assets del otro cliente. El 2026-08-12 se encontró uno
 * PUBLICADO en la galería de la home: el Setting `home_gallery_img_2` estaba
 * vacío y el fallback hardcodeado del blade apuntaba a
 * `tours/machu-picchu-paquete-de-4-dias-lima-view-tours.jpg`.
 *
 * La lección que fija este test: un default que publica material ajeno es un
 * defecto, no un pendiente de configuración. No alcanza con vaciar el Setting
 * ni con borrar el archivo — mientras el fallback siga escrito en el código,
 * cualquier instalación nueva lo vuelve a publicar.
 */
class NoForeignClientAssetsTest extends TestCase
{
    use RefreshDatabase;

    /** Nombres de otros clientes que no deben aparecer en assets de este proyecto. */
    private const FOREIGN = ['lima-view', 'limaview', 'lima_view'];

    public function test_ninguna_vista_referencia_un_asset_de_otro_cliente(): void
    {
        $offenders = [];

        foreach ($this->phpFilesIn(resource_path('views')) as $file) {
            $contents = strtolower((string) file_get_contents($file));

            foreach (self::FOREIGN as $needle) {
                // Solo interesa cuando el nombre ajeno viaja dentro de una ruta de
                // imagen: el texto "Lima View" en un comentario que explica este
                // mismo problema no es un defecto.
                if (preg_match('#[\w/\-]*'.preg_quote($needle, '#').'[\w\-]*\.(jpg|jpeg|png|webp|gif|svg)#', $contents, $m)) {
                    $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file).' → '.$m[0];
                }
            }
        }

        $this->assertSame([], $offenders, "Hay vistas que referencian imágenes de otro cliente:\n".implode("\n", $offenders));
    }

    public function test_no_quedan_archivos_de_otro_cliente_en_el_storage_publico(): void
    {
        $found = [];

        foreach ([storage_path('app/public'), public_path('media'), public_path('assets')] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

            foreach ($it as $file) {
                $name = strtolower($file->getFilename());

                foreach (self::FOREIGN as $needle) {
                    if (str_contains($name, $needle)) {
                        $found[] = $file->getPathname();
                    }
                }
            }
        }

        $this->assertSame([], $found, "Hay archivos de otro cliente en el proyecto:\n".implode("\n", $found));
    }

    public function test_la_base_de_datos_no_apunta_a_un_asset_de_otro_cliente(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $columns = [
            ['tours', 'cover_image'], ['tours', 'gallery'],
            ['regions', 'hero_image'], ['settings', 'value'],
            ['blog_posts', 'cover_image'],
        ];

        $hits = [];

        foreach ($columns as [$table, $column]) {
            foreach (self::FOREIGN as $needle) {
                $rows = DB::table($table)->where($column, 'like', '%'.$needle.'%')->count();

                if ($rows > 0) {
                    $hits[] = "$table.$column tiene $rows fila(s) con '$needle'";
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_la_home_servida_no_entrega_ninguna_imagen_de_otro_cliente(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $html = $this->get('/es')->assertOk()->getContent();

        // Se miran las URLs de imagen realmente servidas, no el HTML entero: así
        // el test no se vuelve verde por casualidad si alguien renombra el archivo
        // pero lo sigue publicando.
        preg_match_all('#(?:src|srcset|url\()["\']?([^"\'\s)]+\.(?:jpg|jpeg|png|webp|gif))#i', $html, $m);

        $offenders = array_values(array_filter($m[1] ?? [], function (string $url) {
            $url = strtolower($url);

            foreach (self::FOREIGN as $needle) {
                if (str_contains($url, $needle)) {
                    return true;
                }
            }

            return false;
        }));

        $this->assertSame([], $offenders, "La home sirve imágenes de otro cliente:\n".implode("\n", $offenders));
    }

    /** @return list<string> */
    private function phpFilesIn(string $dir): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
