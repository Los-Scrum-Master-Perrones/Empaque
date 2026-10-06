<?php

namespace Tests\Feature;

use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vineta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TablePerPageOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_displays_every_vineta_and_product(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('productos.ver', 'web');
        $user->givePermissionTo('productos.ver');

        foreach (range(1, 12) as $index) {
            Vineta::create([
                'api_id' => 4000 + $index,
                'impreso' => true,
            ]);
            Producto::create([
                'api_id_producto' => 5000 + $index,
                'nombre' => sprintf('Producto %02d', $index),
            ]);
        }

        $vinetasResponse = $this->actingAs($user)->get('/vinetas?per_page=all');

        $vinetasResponse->assertOk()
            ->assertSee('<option value="all" selected>Todos</option>', false);
        $this->assertSame(
            12,
            substr_count($vinetasResponse->getContent(), 'class="vinetas-table-row')
        );

        $productosResponse = $this->actingAs($user)->get('/catalogos/productos?per_page=all');

        $productosResponse->assertOk()
            ->assertSee('<option value="all" selected>Todos</option>', false);
        $this->assertSame(
            12,
            substr_count($productosResponse->getContent(), 'class="theme-row border-b')
        );
    }

    public function test_vinetas_can_filter_each_visible_qr_field_with_actions_below(): void
    {
        $user = User::factory()->create();
        $presentacionCaja = Presentacion::create(['nombre' => 'Caja de 20']);
        $presentacionPaquete = Presentacion::create(['nombre' => 'Paquete de 10']);
        Producto::create([
            'api_id_producto' => 8001,
            'codigo_producto' => 'CP-100',
            'presentacion_id' => $presentacionCaja->id,
        ]);
        Producto::create([
            'api_id_producto' => 8002,
            'codigo_producto' => 'CP-200',
            'presentacion_id' => $presentacionPaquete->id,
        ]);
        Vineta::create([
            'api_id' => 7001,
            'marca' => 'Marca Norte',
            'nombre' => 'Toro Especial',
            'capa' => 'Habano Claro',
            'vitola' => 'Toro',
            'tipo_empaque' => 'Caja de 20',
            'codigo_producto' => 'CP-100',
            'item' => 'ITEM-100',
            'orden_del_sistema' => 'OS-100',
            'orden' => 'OC-100',
            'impreso' => true,
        ]);
        Vineta::create([
            'api_id' => 7002,
            'marca' => 'Marca Sur',
            'nombre' => 'Robusto Reserva',
            'capa' => 'Maduro',
            'vitola' => 'Robusto',
            'tipo_empaque' => 'Paquete de 10',
            'codigo_producto' => 'CP-200',
            'item' => 'ITEM-200',
            'orden_del_sistema' => 'OS-200',
            'orden' => 'OC-200',
            'impreso' => true,
        ]);
        $filters = [
            'marca' => 'Norte',
            'nombre' => 'Toro Especial',
            'codigo_producto' => 'CP-100',
            'capa' => 'Claro',
            'item' => 'ITEM-100',
            'orden_del_sistema' => 'OS-100',
            'orden_cliente' => 'OC-100',
        ];

        foreach ($filters as $field => $value) {
            $response = $this->actingAs($user)->get(route('vinetas.index', [$field => $value]));

            $response->assertOk();
            $this->assertSame(1, substr_count($response->getContent(), 'class="vinetas-table-row'));
            $response->assertSee('#7001')->assertDontSee('#7002');
        }

        $combinedResponse = $this->actingAs($user)->get(route('vinetas.index', array_merge($filters, [
            'orden' => 'orden',
            'direccion' => 'asc',
        ])));

        $combinedResponse->assertOk()
            ->assertSee('vinetas-filter-row', false)
            ->assertSee('vinetas-filter-actions', false)
            ->assertSee('name="marca"', false)
            ->assertSee('name="nombre"', false)
            ->assertSee('name="codigo_producto"', false)
            ->assertSee('name="capa"', false)
            ->assertSee('name="item"', false)
            ->assertSee('name="orden_del_sistema"', false)
            ->assertSee('name="orden_cliente"', false)
            ->assertDontSee('name="vitola"', false)
            ->assertDontSee('name="tipo_empaque"', false)
            ->assertDontSee('vinetas-filter-scroll', false)
            ->assertDontSee('Filtros:', false)
            ->assertSee('Caja de 20')
            ->assertSee('#7001')
            ->assertDontSee('#7002');

        $header = $this->tableSection($combinedResponse->getContent(), 'thead');
        $this->assertStringsAppearInOrder($header, [
            'Fecha',
            'ID API',
            'Item',
            'Presentación',
            'Código de producto',
            'Marca',
            'Nombre',
            'Vitola',
            'Capa',
            'Orden del sistema',
            'Orden del cliente',
            'Tipo de empaque',
            'Mes',
            'Puros',
            'Estado',
            'Impreso',
        ]);

        $sortedResponse = $this->actingAs($user)->get(route('vinetas.index', [
            'orden' => 'presentacion',
            'direccion' => 'asc',
        ]));
        $body = $this->tableSection($sortedResponse->getContent(), 'tbody');
        $positionCaja = strpos($body, '#7001');
        $positionPaquete = strpos($body, '#7002');

        $sortedResponse->assertOk();
        $this->assertNotFalse($positionCaja);
        $this->assertNotFalse($positionPaquete);
        $this->assertLessThan($positionPaquete, $positionCaja);
    }

    private function tableSection(string $content, string $tag): string
    {
        $start = strpos($content, "<{$tag}");
        $end = $start === false ? false : strpos($content, "</{$tag}>", $start);

        return $start === false || $end === false
            ? ''
            : substr($content, $start, $end - $start);
    }

    private function assertStringsAppearInOrder(string $content, array $strings): void
    {
        $offset = 0;

        foreach ($strings as $string) {
            $position = strpos($content, $string, $offset);

            $this->assertNotFalse($position, "No se encontró {$string} en el orden esperado.");
            $offset = $position + strlen($string);
        }
    }
}
