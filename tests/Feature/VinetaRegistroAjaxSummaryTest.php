<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\Presentacion;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VinetaRegistroAjaxSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_filters_update_summary_and_table_fragments(): void
    {
        $user = User::factory()->create();
        $vinetaUno = Vineta::create([
            'api_id' => 1001,
            'impreso' => true,
        ]);
        $vinetaDos = Vineta::create([
            'api_id' => 1002,
            'impreso' => true,
        ]);
        $presentacion = Presentacion::create(['nombre' => 'Caja de 20']);
        $producto = Producto::create([
            'api_id_producto' => 9001,
            'item' => 'ITEM-001',
            'codigo_producto' => 'PROD-001',
            'nombre' => 'Toro Clásico',
            'presentacion_id' => $presentacion->id,
        ]);

        $this->createRegistro($vinetaUno, [
            'producto_id' => $producto->id,
            'producto_codigo' => 'PROD-001',
            'producto_item' => 'ITEM-001',
            'producto_nombre' => 'Toro Clásico',
            'marca' => 'Reserva Original',
            'vitola' => 'Toro',
            'capa' => 'Habano',
            'orden_del_sistema' => 'OS-001',
            'orden' => 'OC-001',
            'empleado_codigo' => 'EMP-001',
            'empleado_nombre' => 'Empleado Uno',
            'cantidad_puros' => 100,
            'cantidad_cajones' => 2,
            'cantidad_actividades' => 3,
        ]);
        $this->createRegistro($vinetaDos, [
            'empleado_codigo' => 'EMP-002',
            'empleado_nombre' => 'Empleado Dos',
            'cantidad_puros' => 40,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('vineta-registros.index', ['empleado' => 'EMP-001']));

        $response->assertOk()
            ->assertSee('id="vinetaRegistrosSummaryResponse"', false)
            ->assertSee('id="vinetaRegistrosTableResponse"', false)
            ->assertSee('>1</p>', false)
            ->assertSee('>100</p>', false)
            ->assertSee('>2</p>', false)
            ->assertSee('>300</p>', false)
            ->assertSee('Empleado Uno')
            ->assertDontSee('Empleado Dos')
            ->assertSeeInOrder([
                'Item',
                'Presentación',
                'Código de producto',
                'Marca',
                'Nombre',
                'Vitola',
                'Capa',
                'Tipo de empaque',
                'Orden del sistema',
                'Orden del cliente',
            ])
            ->assertSee('Caja de 20')
            ->assertSee('PROD-001')
            ->assertSee('Reserva Original')
            ->assertSee('Toro Clásico')
            ->assertSee('Habano')
            ->assertSee('ITEM-001')
            ->assertSee('OS-001')
            ->assertSee('OC-001');

        $this->actingAs($user)
            ->getJson(route('vineta-registros.seguimiento', $vinetaUno))
            ->assertOk()
            ->assertJsonCount(1, 'timeline')
            ->assertJsonPath('summary.producto', 'Toro Clásico')
            ->assertJsonPath('summary.orden', 'OC-001');
    }

    public function test_it_filters_registered_vinetas_by_each_separate_field(): void
    {
        $user = User::factory()->create();
        $vinetaA = Vineta::create(['api_id' => 4001, 'impreso' => true]);
        $vinetaB = Vineta::create(['api_id' => 4002, 'impreso' => true]);

        $this->createRegistro($vinetaA, [
            'producto_codigo' => 'PROD-FILTRO-A',
            'producto_item' => 'ITEM-FILTRO-A',
            'orden_del_sistema' => 'OS-FILTRO-A',
            'orden' => 'OC-FILTRO-A',
            'empleado_codigo' => 'EMP-FILTRO-A',
            'empleado_nombre' => 'Empleado Filtro A',
            'actividad_nombre' => 'Rezagado Family',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);
        $this->createRegistro($vinetaB, [
            'producto_codigo' => 'PROD-FILTRO-B',
            'producto_item' => 'ITEM-FILTRO-B',
            'orden_del_sistema' => 'OS-FILTRO-B',
            'orden' => 'OC-FILTRO-B',
            'empleado_codigo' => 'EMP-FILTRO-B',
            'empleado_nombre' => 'Empleado Filtro B',
            'actividad_nombre' => 'Anillado',
            'fecha_registro' => '2026-08-13',
            'registrado_en' => '2026-08-13 08:00:00',
            'cantidad_puros' => 40,
            'cantidad_cajones' => 2,
            'cantidad_actividades' => 1,
        ]);
        EmpleadoHoraOrdinaria::create([
            'empleado_codigo' => 'ORD-FILTRO',
            'empleado_nombre' => 'Ordinario Filtro',
            'fecha' => '2026-08-12',
            'minutos' => 60,
            'observacion' => 'Apoyo',
        ]);

        $filters = [
            'id_vineta' => '4001',
            'item' => 'ITEM-FILTRO-A',
            'orden_del_sistema' => 'OS-FILTRO-A',
            'orden_cliente' => 'OC-FILTRO-A',
            'codigo_producto' => 'PROD-FILTRO-A',
            'empleado' => 'EMP-FILTRO-A',
            'actividad_grupo' => 'rezago',
        ];

        foreach ($filters as $field => $value) {
            $response = $this->actingAs($user)->get(route('vineta-registros.index', [$field => $value]));

            $response->assertOk()
                ->assertSee('Empleado Filtro A')
                ->assertDontSee('Empleado Filtro B');
            $this->assertSame(1, substr_count($response->getContent(), 'class="vinetas-table-row'));
        }

        $combinedResponse = $this->actingAs($user)->get(route('vineta-registros.index', $filters + [
            'fecha_desde' => '2026-08-12',
            'fecha_hasta' => '2026-08-12',
        ]));

        $combinedResponse->assertOk()
            ->assertSee('name="id_vineta"', false)
            ->assertSee('name="item"', false)
            ->assertSee('name="orden_del_sistema"', false)
            ->assertSee('name="orden_cliente"', false)
            ->assertSee('name="codigo_producto"', false)
            ->assertSee('name="empleado"', false)
            ->assertSee('name="capa"', false)
            ->assertSee('name="actividad_grupo"', false)
            ->assertSee('name="fecha_desde"', false)
            ->assertSee('name="fecha_hasta"', false)
            ->assertSee('vineta-registros-filter-row', false)
            ->assertDontSee('name="buscar"', false)
            ->assertDontSee('name="estado"', false)
            ->assertDontSee('Ordinario Filtro');
    }

    public function test_it_finds_special_records_when_filtering_by_activity_group(): void
    {
        $user = User::factory()->create();
        $specialActivities = [
            15346 => ['EMP-PETACA', 'Empleado Petaca', 'Petaca 4 Puros'],
            15347 => ['EMP-TUBO', 'Empleado Tubo', 'Hecha de Paquete TUBO/5'],
            15348 => ['EMP-SAMPLER', 'Empleado Sampler', 'Sampler COTSCO 10 Puros'],
            15349 => ['EMP-ESPONJA', 'Empleado Esponja', 'Esponja'],
            15350 => ['EMP-LAMINA', 'Empleado Lamina', 'Lamina'],
            15351 => ['EMP-SELLO', 'Empleado Sello', 'Pegado de sello en celofan'],
        ];

        foreach ($specialActivities as $apiId => [$codigo, $nombre, $actividad]) {
            $vineta = Vineta::create(['api_id' => $apiId, 'impreso' => true]);
            $this->createRegistro($vineta, [
                'empleado_codigo' => $codigo,
                'empleado_nombre' => $nombre,
                'actividad_nombre' => $actividad,
                'cantidad_puros' => 20,
                'cantidad_cajones' => 1,
                'cantidad_actividades' => 4,
            ]);
        }

        $other = Vineta::create(['api_id' => 15352, 'impreso' => true]);
        $this->createRegistro($other, [
            'empleado_codigo' => 'EMP-OTRO',
            'empleado_nombre' => 'Empleado Otro',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.index', [
            'id_vineta' => '15346',
            'actividad_grupo' => 'llenado',
        ]));

        $response->assertOk()
            ->assertSee('Petaca 4 Puros')
            ->assertSee('Empleado Petaca')
            ->assertDontSee('Empleado Otro');
        $this->assertSame(1, substr_count($response->getContent(), 'class="vinetas-table-row'));

        $llenadoResponse = $this->actingAs($user)->get(route('vineta-registros.index', [
            'actividad_grupo' => 'llenado',
        ]));
        $llenadoResponse->assertOk()
            ->assertSee('Hecha de Paquete TUBO/5')
            ->assertSee('Sampler COTSCO 10 Puros')
            ->assertDontSee('Esponja')
            ->assertDontSee('Empleado Otro');

        $anilladoResponse = $this->actingAs($user)->get(route('vineta-registros.index', [
            'actividad_grupo' => 'anillado',
        ]));
        $anilladoResponse->assertOk()
            ->assertSee('Esponja')
            ->assertSee('Lamina')
            ->assertSee('Pegado de sello en celofan')
            ->assertDontSee('Petaca 4 Puros')
            ->assertDontSee('Empleado Otro');
    }

    public function test_registered_vinetas_table_prefers_vineta_product_name_and_packaging_type_over_catalog_placeholders(): void
    {
        $user = User::factory()->create();
        $producto = Producto::create([
            'api_id_producto' => 9201,
            'nombre' => 'Ninguna',
        ]);
        $vineta = Vineta::create([
            'api_id' => 15408,
            'nombre' => 'Producto completo desde API',
            'tipo_empaque' => 'Caja especial API',
            'impreso' => true,
        ]);

        $this->createRegistro($vineta, [
            'producto_id' => $producto->id,
            'producto_nombre' => 'Ninguna',
            'tipo_empaque' => 'Ninguna',
            'empleado_codigo' => 'EMP-API',
            'empleado_nombre' => 'Empleado API',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.index', [
            'id_vineta' => '15408',
        ]));

        $response->assertOk();
        $body = $this->tableBody($response->getContent());

        $this->assertStringContainsString('Producto completo desde API', $body);
        $this->assertStringContainsString('Caja especial API', $body);
        $this->assertStringNotContainsString('Ninguna', $body);
    }

    public function test_registered_vinetas_table_prefers_vineta_data_over_catalog_data(): void
    {
        $user = User::factory()->create();
        $producto = Producto::create([
            'api_id_producto' => 9202,
            'codigo_producto' => 'CAT-COD-001',
            'item' => 'CAT-ITEM-001',
            'nombre' => 'Producto Cat',
            'marca' => 'Marca Cat',
            'capa' => 'Capa Cat',
            'vitola' => 'Vitola Cat',
            'tipo_empaque' => 'Empaque Cat',
        ]);
        $vineta = Vineta::create([
            'api_id' => 15409,
            'codigo_producto' => 'VIN-COD-999',
            'item' => 'VIN-ITEM-999',
            'nombre' => 'Producto Viñeta',
            'marca' => 'Marca Viñeta',
            'capa' => 'Capa Viñeta',
            'vitola' => 'Vitola Viñeta',
            'tipo_empaque' => 'Empaque Viñeta',
            'orden' => 'ORD-VIN-777',
            'orden_del_sistema' => 'OS-VIN-888',
            'impreso' => true,
        ]);

        // Even if the registro had historical catalog data, it should prioritize viñeta
        $this->createRegistro($vineta, [
            'producto_id' => $producto->id,
            'producto_codigo' => 'CAT-COD-001',
            'producto_item' => 'CAT-ITEM-001',
            'producto_nombre' => 'Producto Cat',
            'marca' => 'Marca Cat',
            'capa' => 'Capa Cat',
            'vitola' => 'Vitola Cat',
            'tipo_empaque' => 'Empaque Cat',
            'orden' => 'ORD-CAT-111',
            'orden_del_sistema' => 'OS-CAT-222',
            'empleado_codigo' => 'EMP-TEST',
            'empleado_nombre' => 'Empleado Test',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.index', [
            'id_vineta' => '15409',
        ]));

        $response->assertOk();
        $body = $this->tableBody($response->getContent());

        $this->assertStringContainsString('VIN-COD-999', $body);
        $this->assertStringContainsString('VIN-ITEM-999', $body);
        $this->assertStringContainsString('Producto Viñeta', $body);
        $this->assertStringContainsString('Marca Viñeta', $body);
        $this->assertStringContainsString('Capa Viñeta', $body);
        $this->assertStringContainsString('Vitola Viñeta', $body);
        $this->assertStringContainsString('Empaque Viñeta', $body);
        $this->assertStringContainsString('ORD-VIN-777', $body);
        $this->assertStringContainsString('OS-VIN-888', $body);

        $this->assertStringNotContainsString('CAT-COD-001', $body);
        $this->assertStringNotContainsString('CAT-ITEM-001', $body);
    }

    public function test_it_paginates_combined_records_in_the_database(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 12) as $index) {
            $vineta = Vineta::create([
                'api_id' => 2000 + $index,
                'impreso' => true,
            ]);
            $this->createRegistro($vineta, [
                'empleado_codigo' => sprintf('EMP-%03d', $index),
                'empleado_nombre' => sprintf('Empleado %03d', $index),
                'cantidad_puros' => 20,
                'cantidad_cajones' => 1,
                'cantidad_actividades' => 1,
            ]);
        }

        foreach (range(1, 2) as $index) {
            EmpleadoHoraOrdinaria::create([
                'empleado_codigo' => sprintf('ORD-%03d', $index),
                'empleado_nombre' => sprintf('Ordinario %03d', $index),
                'fecha' => '2026-08-12',
                'minutos' => 60,
                'observacion' => 'Apoyo',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(
            '/vinetas-registradas?per_page=10&orden=empleado_nombre&direccion=asc'
        );

        $response->assertOk();
        $this->assertSame(
            10,
            substr_count($response->getContent(), 'class="vinetas-table-row')
        );
        $this->assertTrue(
            collect(DB::getQueryLog())->contains(
                fn (array $query) => str_contains(strtolower($query['query']), 'limit 10')
            ),
            'La consulta debe paginar en la base de datos con LIMIT 10.'
        );

        $allResponse = $this->actingAs($user)->get('/vinetas-registradas?per_page=all');

        $allResponse->assertOk()
            ->assertSee('<option value="all" selected>Todos</option>', false)
            ->assertSee('"timelines":[],"summaries":[]', false);
        // Debe mostrar exactamente las 12 viñetas excluyendo las horas ordinarias
        $this->assertSame(
            12,
            substr_count($allResponse->getContent(), 'class="vinetas-table-row')
        );
    }

    public function test_it_sorts_by_product_and_order_columns_in_the_database(): void
    {
        $user = User::factory()->create();
        $presentacionZ = Presentacion::create(['nombre' => 'Presentación Z']);
        $presentacionA = Presentacion::create(['nombre' => 'Presentación A']);
        $productoZ = Producto::create([
            'api_id_producto' => 9101,
            'nombre' => 'Producto Z',
            'presentacion_id' => $presentacionZ->id,
        ]);
        $productoA = Producto::create([
            'api_id_producto' => 9102,
            'nombre' => 'Producto A',
            'presentacion_id' => $presentacionA->id,
        ]);
        $vinetaZ = Vineta::create(['api_id' => 3001, 'tipo_empaque' => 'Tipo Z', 'impreso' => true]);
        $vinetaA = Vineta::create(['api_id' => 3002, 'tipo_empaque' => 'Tipo A', 'impreso' => true]);

        $this->createRegistro($vinetaZ, [
            'producto_id' => $productoZ->id,
            'producto_codigo' => 'PROD-Z',
            'producto_item' => 'ITEM-Z',
            'producto_nombre' => 'Nombre Z',
            'marca' => 'Marca Z',
            'vitola' => 'Vitola Z',
            'capa' => 'Capa Z',
            'tipo_empaque' => 'Tipo Z registro',
            'orden_del_sistema' => 'OS-Z',
            'orden' => 'OC-Z',
            'empleado_codigo' => 'EMP-SORT-Z',
            'empleado_nombre' => 'Empleado Z',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);
        $this->createRegistro($vinetaA, [
            'producto_id' => $productoA->id,
            'producto_codigo' => 'PROD-A',
            'producto_item' => 'ITEM-A',
            'producto_nombre' => 'Nombre A',
            'marca' => 'Marca A',
            'vitola' => 'Vitola A',
            'capa' => 'Capa A',
            'tipo_empaque' => 'Tipo A registro',
            'orden_del_sistema' => 'OS-A',
            'orden' => 'OC-A',
            'empleado_codigo' => 'EMP-SORT-A',
            'empleado_nombre' => 'Empleado A',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        foreach ([
            'presentacion',
            'producto_codigo',
            'marca',
            'producto_nombre',
            'vitola',
            'capa',
            'tipo_empaque',
            'producto_item',
            'orden_del_sistema',
            'orden',
        ] as $field) {
            $response = $this->actingAs($user)->get(route('vineta-registros.index', [
                'orden' => $field,
                'direccion' => 'asc',
            ]));

            $response->assertOk();
            $body = $this->tableBody($response->getContent());
            $positionA = strpos($body, 'EMP-SORT-A');
            $positionZ = strpos($body, 'EMP-SORT-Z');

            $this->assertNotFalse($positionA);
            $this->assertNotFalse($positionZ);
            $this->assertTrue($positionA < $positionZ, "No se ordenó correctamente por {$field}.");
        }
    }

    public function test_it_displays_and_sorts_by_responsable_column(): void
    {
        $user = User::factory()->create(['name' => 'Supervisor Principal']);
        $vinetaA = Vineta::create(['api_id' => 4001, 'tipo_empaque' => 'Caja', 'impreso' => true]);
        $vinetaZ = Vineta::create(['api_id' => 4002, 'tipo_empaque' => 'Caja', 'impreso' => true]);

        $this->createRegistro($vinetaA, [
            'empleado_codigo' => 'EMP-RESP-A',
            'empleado_nombre' => 'Empleado A',
            'registrado_por_nombre' => 'Ana Garcia',
            'cantidad_puros' => 10,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $this->createRegistro($vinetaZ, [
            'empleado_codigo' => 'EMP-RESP-Z',
            'empleado_nombre' => 'Empleado Z',
            'registrado_por_nombre' => 'Zulema Romero',
            'cantidad_puros' => 10,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $response = $this->actingAs($user)->get('/vinetas-registradas');
        $response->assertOk();
        $response->assertSee('Responsable');
        $response->assertSee('Ana Garcia');
        $response->assertSee('Zulema Romero');

        $ascResponse = $this->actingAs($user)->get(route('vineta-registros.index', [
            'orden' => 'registrado_por_nombre',
            'direccion' => 'asc',
        ]));
        $ascResponse->assertOk();
        $ascBody = $this->tableBody($ascResponse->getContent());
        $posA = strpos($ascBody, 'Ana Garcia');
        $posZ = strpos($ascBody, 'Zulema Romero');
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posZ);
        $this->assertTrue($posA < $posZ);

        $descResponse = $this->actingAs($user)->get(route('vineta-registros.index', [
            'orden' => 'registrado_por_nombre',
            'direccion' => 'desc',
        ]));
        $descResponse->assertOk();
        $descBody = $this->tableBody($descResponse->getContent());
        $posADesc = strpos($descBody, 'Ana Garcia');
        $posZDesc = strpos($descBody, 'Zulema Romero');
        $this->assertNotFalse($posADesc);
        $this->assertNotFalse($posZDesc);
        $this->assertTrue($posZDesc < $posADesc);
    }

    public function test_it_filters_by_indirectos_group_and_subactivity_in_web_table(): void
    {
        $user = User::factory()->create();

        $empIndirecto = Empleado::create([
            'codigo' => 'EMP-CALIDAD',
            'nombre' => 'Inspector Calidad',
            'cargo' => 'Revisadora de Calidad',
            'activo' => true,
        ]);

        $empDirecto = Empleado::create([
            'codigo' => 'EMP-REZAGO',
            'nombre' => 'Operador Rezago',
            'cargo' => 'Rezagadora de puros',
            'activo' => true,
        ]);

        $vinetaA = Vineta::create(['api_id' => 9101, 'impreso' => true]);
        $vinetaB = Vineta::create(['api_id' => 9102, 'impreso' => true]);

        $this->createRegistro($vinetaA, [
            'empleado_id' => $empIndirecto->id,
            'empleado_codigo' => $empIndirecto->codigo,
            'empleado_nombre' => $empIndirecto->nombre,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-12',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        $this->createRegistro($vinetaB, [
            'empleado_id' => $empDirecto->id,
            'empleado_codigo' => $empDirecto->codigo,
            'empleado_nombre' => $empDirecto->nombre,
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-12',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
        ]);

        // Filtrar actividad_grupo = indirectos
        $resIndirectos = $this->actingAs($user)->get(route('vineta-registros.index', [
            'actividad_grupo' => 'indirectos',
        ]));
        $resIndirectos->assertOk()
            ->assertSee('Inspector Calidad')
            ->assertDontSee('Operador Rezago');

        // Filtrar actividad_grupo = indirectos_rezago
        $resIndirectosRezago = $this->actingAs($user)->get(route('vineta-registros.index', [
            'actividad_grupo' => 'indirectos_rezago',
        ]));
        $resIndirectosRezago->assertOk()
            ->assertSee('Inspector Calidad')
            ->assertDontSee('Operador Rezago');
    }

    private function createRegistro(Vineta $vineta, array $attributes): VinetaRegistro
    {

        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$attributes['empleado_codigo'],
            'vineta_api_id' => $vineta->api_id,
            'producto_nombre' => 'Producto prueba',
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-12',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-12 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }

    private function tableBody(string $content): string
    {
        $start = strpos($content, '<tbody');
        $end = $start === false ? false : strpos($content, '</tbody>', $start);

        return $start === false || $end === false
            ? ''
            : substr($content, $start, $end - $start);
    }
}
