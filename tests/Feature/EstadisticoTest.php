<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadisticoTest extends TestCase
{
    use RefreshDatabase;

    public function test_estadistico_requiere_autenticacion(): void
    {
        $response = $this->get(route('estadistico.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_estadistico_agrupa_por_orden_sistema_orden_cliente_e_item_con_actividades_y_subtotales(): void
    {
        $user = User::factory()->create();
        $vineta = Vineta::create(['api_id' => 2001, 'impreso' => true]);

        // Grupo 1: Orden Sistema = OS-100, Orden Cliente = OC-500, Item = ITEM-01
        // Actividad 1: Anillado 2000
        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-100',
            'orden' => 'OC-500',
            'producto_codigo' => 'PROD-A',
            'producto_item' => 'ITEM-01',
            'producto_nombre' => 'Robusto',
            'marca' => 'Plasencia Reserva',
            'vitola' => '5x50',
            'capa' => 'Habano',
            'tipo_empaque' => 'CAJAS 1/20',
            'actividad_nombre' => 'Anillado',
            'cantidad_puros' => 2000,
            'empleado_codigo' => 'EMP-01',
            'empleado_nombre' => 'Juan Pérez',
            'fecha_registro' => '2026-09-20',
        ]);

        // Actividad 2: Llenado 2000
        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-100',
            'orden' => 'OC-500',
            'producto_codigo' => 'PROD-A',
            'producto_item' => 'ITEM-01',
            'producto_nombre' => 'Robusto',
            'marca' => 'Plasencia Reserva',
            'vitola' => '5x50',
            'capa' => 'Habano',
            'tipo_empaque' => 'CAJAS 1/20',
            'actividad_nombre' => 'Llenado',
            'cantidad_puros' => 2000,
            'empleado_codigo' => 'EMP-02',
            'empleado_nombre' => 'María López',
            'fecha_registro' => '2026-09-20',
        ]);

        // Actividad 3: Rezagado 2000
        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-100',
            'orden' => 'OC-500',
            'producto_codigo' => 'PROD-A',
            'producto_item' => 'ITEM-01',
            'producto_nombre' => 'Robusto',
            'marca' => 'Plasencia Reserva',
            'vitola' => '5x50',
            'capa' => 'Habano',
            'tipo_empaque' => 'CAJAS 1/20',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 2000,
            'empleado_codigo' => 'EMP-03',
            'empleado_nombre' => 'Carlos Ruiz',
            'fecha_registro' => '2026-09-20',
        ]);

        // Grupo 2: Otro item
        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-200',
            'orden' => 'OC-600',
            'producto_codigo' => 'PROD-B',
            'producto_item' => 'ITEM-02',
            'producto_nombre' => 'Toro',
            'marca' => 'Alma Fuerte',
            'vitola' => '6x52',
            'capa' => 'Maduro',
            'tipo_empaque' => 'MAZOS 1/10',
            'actividad_nombre' => 'Empaque Manual',
            'cantidad_puros' => 1500,
            'empleado_codigo' => 'EMP-04',
            'empleado_nombre' => 'Ana Gómez',
            'fecha_registro' => '2026-09-21',
        ]);

        $response = $this->actingAs($user)->get(route('estadistico.index'));

        $response->assertOk();
        $response->assertSee('Estadístico');
        // Columnas principales
        $response->assertSee('Orden del sistema');
        $response->assertSee('Orden del cliente');
        $response->assertSee('Código producto');
        $response->assertSee('Item');
        $response->assertSee('Descripción');
        $response->assertSee('Tipo de empaque');
        $response->assertSee('Actividad');
        $response->assertSee('Cantidad procesada');

        // Datos del Grupo 1
        $response->assertSee('OS-100');
        $response->assertSee('OC-500');
        $response->assertSee('PROD-A');
        $response->assertSee('ITEM-01');
        $response->assertSee('CAJAS 1/20');
        $response->assertSee('Anillado');
        $response->assertSee('Llenado');
        $response->assertSee('Rezagado');
        $response->assertSee('2,000');
        // Subtotal del grupo 1: 2000 + 2000 + 2000 = 6000
        $response->assertSee('6,000');

        // Datos del Grupo 2
        $response->assertSee('OS-200');
        $response->assertSee('OC-600');
        $response->assertSee('PROD-B');
        $response->assertSee('ITEM-02');
        $response->assertSee('Empaque Manual');
        $response->assertSee('1,500');

        // Verificamos que se removió el Gran Total General y las métricas superiores
        $response->assertDontSee('Gran Total General');
        $response->assertDontSee('Total Grupos');
    }

    public function test_estadistico_filtra_por_rango_de_fechas_y_empleado(): void
    {
        $user = User::factory()->create();
        $vineta = Vineta::create(['api_id' => 2002, 'impreso' => true]);

        // Registro para EMP-10 en fecha 2026-09-10
        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-10',
            'orden' => 'OC-10',
            'producto_codigo' => 'P-10',
            'producto_item' => 'ITEM-10',
            'actividad_nombre' => 'Anillado',
            'cantidad_puros' => 1000,
            'empleado_codigo' => 'EMP-10',
            'empleado_nombre' => 'Diana Morales',
            'fecha_registro' => '2026-09-10',
        ]);

        // Registro para EMP-20 en fecha 2026-09-25
        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-20',
            'orden' => 'OC-20',
            'producto_codigo' => 'P-20',
            'producto_item' => 'ITEM-20',
            'actividad_nombre' => 'Llenado',
            'cantidad_puros' => 3000,
            'empleado_codigo' => 'EMP-20',
            'empleado_nombre' => 'Elena Vásquez',
            'fecha_registro' => '2026-09-25',
        ]);

        // 1. Filtrar por rango de fecha que solo incluya el primer registro
        $responseFechas = $this->actingAs($user)->get(route('estadistico.index', [
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-15',
        ]));

        $responseFechas->assertOk();
        $responseFechas->assertSee('OS-10');
        $responseFechas->assertDontSee('OS-20');

        // 2. Filtrar por empleado (nombre)
        $responseEmp = $this->actingAs($user)->get(route('estadistico.index', [
            'empleado' => 'Elena',
        ]));

        $responseEmp->assertOk();
        $responseEmp->assertSee('OS-20');
        $responseEmp->assertDontSee('OS-10');

        // 3. Filtrar por empleado (código)
        $responseCodigo = $this->actingAs($user)->get(route('estadistico.index', [
            'empleado' => 'EMP-10',
        ]));

        $responseCodigo->assertOk();
        $responseCodigo->assertSee('OS-10');
        $responseCodigo->assertDontSee('OS-20');
    }

    public function test_estadistico_filtra_por_orden_sistema_orden_cliente_e_item(): void
    {
        $user = User::factory()->create();
        $vineta = Vineta::create(['api_id' => 2003, 'impreso' => true]);

        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-ALPHA',
            'orden' => 'OC-CLIENTE-1',
            'producto_codigo' => 'P-A1',
            'producto_item' => 'SKU-111',
            'actividad_nombre' => 'Anillado',
            'cantidad_puros' => 500,
            'fecha_registro' => '2026-09-24',
        ]);

        $this->createRegistro($vineta, [
            'orden_del_sistema' => 'OS-BETA',
            'orden' => 'OC-CLIENTE-2',
            'producto_codigo' => 'P-B2',
            'producto_item' => 'SKU-222',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 800,
            'fecha_registro' => '2026-09-24',
        ]);

        // Filtrar por orden del sistema
        $responseOS = $this->actingAs($user)->get(route('estadistico.index', ['orden_del_sistema' => 'ALPHA']));
        $responseOS->assertOk();
        $responseOS->assertSee('OS-ALPHA');
        $responseOS->assertDontSee('OS-BETA');

        // Filtrar por orden del cliente
        $responseOC = $this->actingAs($user)->get(route('estadistico.index', ['orden_cliente' => 'CLIENTE-2']));
        $responseOC->assertOk();
        $responseOC->assertSee('OS-BETA');
        $responseOC->assertDontSee('OS-ALPHA');

        // Filtrar por item
        $responseItem = $this->actingAs($user)->get(route('estadistico.index', ['item' => '111']));
        $responseItem->assertOk();
        $responseItem->assertSee('SKU-111');
        $responseItem->assertDontSee('SKU-222');
    }

    public function test_estadistico_soporta_peticion_ajax(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('estadistico.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('estadisticoTableInner');
    }

    public function test_estadistico_limpia_texto_ninguna_en_descripcion(): void
    {
        $user = User::factory()->create();
        $vineta = Vineta::create(['api_id' => 2004, 'impreso' => true]);

        $this->createRegistro($vineta, [
            'orden_del_sistema' => '3523',
            'orden' => 'HON-4212',
            'producto_codigo' => 'P-23694',
            'producto_item' => '02009135',
            'producto_nombre' => 'Ninguna',
            'marca' => 'Number 6',
            'vitola' => '6-1/2X52',
            'capa' => 'Corojo',
            'tipo_empaque' => 'Unit of 10',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 100,
            'fecha_registro' => '2026-09-24',
        ]);

        $response = $this->actingAs($user)->get(route('estadistico.index', ['item' => '02009135']));

        $response->assertOk();
        $response->assertSee('Number 6');
        $response->assertSee('6-1/2X52 • Corojo');
        $response->assertDontSee('Ninguna');
    }

    private function createRegistro(Vineta $vineta, array $attributes): VinetaRegistro
    {
        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.($attributes['empleado_codigo'] ?? '001'),
            'vineta_api_id' => $vineta->api_id,
            'empleado_codigo' => 'EMP-001',
            'empleado_nombre' => 'Empleado Test',
            'producto_nombre' => 'Producto prueba',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 100,
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-09-24',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-09-24 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }
}
