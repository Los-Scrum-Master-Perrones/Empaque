<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardAreaRangeTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard.ver', 'web');
        $user->givePermissionTo('dashboard.ver');

        return $user;
    }

    public function test_dashboard_retorna_resumen_mensual_por_defecto(): void
    {
        $this->withoutExceptionHandling();
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Resumen por área');
        $response->assertSee('areaRangeFrom');
        $response->assertSee('areaRangeTo');
    }

    public function test_dashboard_endpoint_ajax_resumen_rango_mes_por_defecto(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'resumen_rango' => 1,
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'ok',
            'is_custom_range',
            'label',
            'fecha_desde',
            'fecha_hasta',
            'total_actividades',
            'areas' => [
                'limpieza' => ['key', 'label', 'actividades', 'actividades_formatted', 'empleados', 'registros', 'puros', 'share'],
                'rezago',
                'anillado',
                'llenado',
            ],
        ]);
        $this->assertFalse($response->json('is_custom_range'));
        $this->assertEquals('#8b5cf6', $response->json('areas.limpieza.color'));
    }

    public function test_dashboard_endpoint_ajax_resumen_con_rango_personalizado(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'resumen_rango' => 1,
            'fecha_desde' => '2026-08-01',
            'fecha_hasta' => '2026-08-15',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('is_custom_range'));
        $this->assertEquals('01/08/2026 - 15/08/2026', $response->json('label'));
        $this->assertEquals('2026-08-01', $response->json('fecha_desde'));
        $this->assertEquals('2026-08-15', $response->json('fecha_hasta'));
    }

    public function test_dashboard_endpoint_ajax_ranking_mes(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'ranking_mes' => 1,
            'mes' => '2026-08',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'ok',
            'mes',
            'label',
            'ranking' => [
                'limpieza' => ['key', 'label', 'color', 'rows'],
                'rezago' => ['key', 'label', 'color', 'rows'],
                'anillado' => ['key', 'label', 'color', 'rows'],
                'llenado' => ['key', 'label', 'color', 'rows'],
            ],
        ]);
        $this->assertTrue($response->json('ok'));
        $this->assertEquals('2026-08', $response->json('mes'));
        $this->assertEquals('Agosto 2026', $response->json('label'));
        $this->assertEquals('#8b5cf6', $response->json('ranking.limpieza.color'));
    }

    public function test_dashboard_resumen_agrupa_por_actividad_general_sin_restringir_cargo_e_incluye_limpieza(): void
    {
        $user = $this->createAuthorizedUser();
        $fecha = '2026-09-03';

        $vineta = Vineta::create([
            'api_id' => 5001,
            'codigo' => 'VIN-DASH-01',
            'estado' => 'registrada',
            'item' => 'ITEM-01',
            'marca' => 'Plasencia',
            'vitola' => 'Robusto',
            'cantidad_puros' => 100,
        ]);

        // Empleado con cargo Rezago realizando actividad de Anillado
        $empRezago = Empleado::create([
            'codigo' => 'EMP-REZ-01',
            'nombre' => 'Rezagadora Uno',
            'cargo' => 'Rezagadora de puros',
            'activo' => true,
        ]);

        // Empleado con cargo Limpieza realizando actividad de Limpieza
        $empLimpieza = Empleado::create([
            'codigo' => 'EMP-LIMP-01',
            'nombre' => 'Limpiadora Uno',
            'cargo' => 'Limpia Puros',
            'activo' => true,
        ]);

        // Registro de Anillado realizado por empleada de rezago
        VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'empleado_id' => $empRezago->id,
            'empleado_codigo' => $empRezago->codigo,
            'empleado_nombre' => $empRezago->nombre,
            'actividad_nombre' => 'Anillado y Celofan',
            'cantidad_puros' => 50,
            'cantidad_actividades' => 1,
            'fecha_registro' => $fecha,
            'hora_registro' => '08:30',
            'registrado_en' => "$fecha 08:30:00",
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        // Registro de Limpieza
        VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'empleado_id' => $empLimpieza->id,
            'empleado_codigo' => $empLimpieza->codigo,
            'empleado_nombre' => $empLimpieza->nombre,
            'actividad_nombre' => 'Limpieza de puros',
            'cantidad_puros' => 40,
            'cantidad_actividades' => 1,
            'fecha_registro' => $fecha,
            'hora_registro' => '09:00',
            'registrado_en' => "$fecha 09:00:00",
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'resumen_rango' => 1,
            'fecha_desde' => $fecha,
            'fecha_hasta' => $fecha,
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $areas = $response->json('areas');

        // Debe contar Anillado aunque el empleado no sea anillador
        $this->assertEquals(1, $areas['anillado']['registros']);
        $this->assertEquals(50, $areas['anillado']['puros']);
        $this->assertEquals(50, $areas['anillado']['actividades']);

        // Debe contar Limpieza
        $this->assertEquals(1, $areas['limpieza']['registros']);
        $this->assertEquals(40, $areas['limpieza']['puros']);
        $this->assertEquals(40, $areas['limpieza']['actividades']);
    }
}
