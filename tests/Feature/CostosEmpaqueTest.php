<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CostosEmpaqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'costos-empaque.ver']);
    }

    public function test_costos_empaque_requiere_autenticacion(): void
    {
        $response = $this->get(route('costos-empaque.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_costos_empaque_requiere_permiso(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('costos-empaque.index'));
        $response->assertForbidden();
    }

    public function test_costos_empaque_muestra_vista_y_agrupa_registros_correctamente(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('costos-empaque.ver');
        $vineta = Vineta::create(['api_id' => 1001, 'impreso' => true]);

        // 2 registros para el mismo empleado y producto
        $this->createRegistro($vineta, [
            'empleado_codigo' => 'EMP-01',
            'empleado_nombre' => 'Juan Pérez',
            'producto_item' => 'ITEM-100',
            'orden_del_sistema' => 'OS-500',
            'orden' => 'OC-900',
            'producto_codigo' => 'P-01',
            'producto_nombre' => 'Robusto',
            'marca' => 'Flor de Copan',
            'vitola' => '5x50',
            'actividad_nombre' => 'Anillado',
            'precio_mo' => 0.0540,
            'cantidad_puros' => 100,
            'cantidad_actividades' => 2, // Total actividades = 200
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-08-20',
        ]);

        $this->createRegistro($vineta, [
            'empleado_codigo' => 'EMP-01',
            'empleado_nombre' => 'Juan Pérez',
            'producto_item' => 'ITEM-100',
            'orden_del_sistema' => 'OS-500',
            'orden' => 'OC-900',
            'producto_codigo' => 'P-01',
            'producto_nombre' => 'Robusto',
            'marca' => 'Flor de Copan',
            'vitola' => '5x50',
            'actividad_nombre' => 'Anillado',
            'precio_mo' => 0.0540,
            'cantidad_puros' => 150,
            'cantidad_actividades' => 2, // Total actividades = 300
            'cantidad_cajones' => 2,
            'fecha_registro' => '2026-08-20',
        ]);

        // Registro de otro empleado / producto
        $this->createRegistro($vineta, [
            'empleado_codigo' => 'EMP-03',
            'empleado_nombre' => 'Mario Rossi',
            'producto_item' => 'ITEM-200',
            'orden_del_sistema' => 'OS-600',
            'orden' => 'OC-950',
            'producto_codigo' => 'P-02',
            'producto_nombre' => 'Toro',
            'marca' => 'Rocky Patel',
            'vitola' => '6x52',
            'actividad_nombre' => 'Rezagado',
            'precio_mo' => 0.0800,
            'cantidad_puros' => 50,
            'cantidad_actividades' => 1, // Total actividades = 50
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-08-21',
        ]);

        $response = $this->actingAs($user)->get(route('costos-empaque.index'));

        $response->assertOk();
        $response->assertSee('Costos empaque');
        $response->assertSee('Juan Pérez');
        $response->assertSee('EMP-01');
        $response->assertSee('ITEM-100');
        $response->assertSee('Flor de Copan');
        $response->assertSee('Robusto');
        $response->assertSee('5x50');
        $response->assertSee('OS-500');
        $response->assertSee('OC-900');
        $response->assertSee('Anillado');
        $response->assertSee('500'); // Suma de cantidad trabajada (200 + 300)
        $response->assertSee('27.0000'); // Total MOD = 500 * 0.0540 = 27.0000
        $response->assertSee('3.00'); // H trabajada = 1 + 2 = 3.00

        $response->assertSee('Mario Rossi');
        $response->assertSee('Rocky Patel');
        $response->assertSee('Toro');
        $response->assertSee('6x52');
        $response->assertSee('4.0000'); // Total MOD = 50 * 0.0800 = 4.0000

        // Verificar que no aparezcan subtítulos eliminados
        $response->assertDontSee('Grupos consolidados');
        $response->assertDontSee('Consolidado de costos y mano de obra directa');
    }

    public function test_costos_empaque_filtros_funcionan(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('costos-empaque.ver');
        $vineta = Vineta::create(['api_id' => 1002, 'impreso' => true]);

        $this->createRegistro($vineta, [
            'empleado_codigo' => 'EMP-ALPHA',
            'empleado_nombre' => 'Empleado Alpha',
            'producto_item' => 'ITEM-ALPHA',
            'orden_del_sistema' => 'OS-111',
            'orden' => 'OC-111',
            'producto_nombre' => 'Robusto',
            'marca' => 'Marca Uno',
            'vitola' => '5x50',
            'actividad_nombre' => 'Anillado',
            'precio_mo' => 0.05,
            'cantidad_puros' => 100,
            'cantidad_actividades' => 1,
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-08-20',
        ]);

        $this->createRegistro($vineta, [
            'empleado_codigo' => 'EMP-BETA',
            'empleado_nombre' => 'Empleado Beta',
            'producto_item' => 'ITEM-BETA',
            'orden_del_sistema' => 'OS-222',
            'orden' => 'OC-222',
            'producto_nombre' => 'Corona',
            'marca' => 'Marca Dos',
            'vitola' => '6x44',
            'actividad_nombre' => 'Rezagado',
            'precio_mo' => 0.07,
            'cantidad_puros' => 80,
            'cantidad_actividades' => 1,
            'cantidad_cajones' => 2,
            'fecha_registro' => '2026-08-22',
        ]);

        // Filtrar por empleado
        $response = $this->actingAs($user)->get(route('costos-empaque.index', ['empleado' => 'Alpha']));
        $response->assertOk();
        $response->assertSee('Empleado Alpha');
        $response->assertDontSee('Empleado Beta');

        // Filtrar por fecha desde
        $responseDesde = $this->actingAs($user)->get(route('costos-empaque.index', ['fecha_desde' => '2026-08-21']));
        $responseDesde->assertOk();
        $responseDesde->assertSee('Empleado Beta');
        $responseDesde->assertDontSee('Empleado Alpha');
    }

    public function test_costos_empaque_ajax_tabla(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('costos-empaque.ver');

        $response = $this->actingAs($user)->get(route('costos-empaque.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('costosEmpaqueTableInner');
    }

    private function createRegistro(Vineta $vineta, array $attributes): VinetaRegistro
    {
        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.($attributes['empleado_codigo'] ?? '001'),
            'vineta_api_id' => $vineta->api_id,
            'producto_nombre' => 'Producto prueba',
            'actividad_nombre' => 'Rezagado',
            'fecha_registro' => '2026-08-12',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-12 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }
}
